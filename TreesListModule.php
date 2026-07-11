<?php

declare(strict_types=1);

namespace TreesListModule;

use Fisharebest\Localization\Translation;
use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Module\HtmlBlockModule;
use Fisharebest\Webtrees\Module\ModuleBlockInterface;
use Fisharebest\Webtrees\Module\ModuleBlockTrait;
use Fisharebest\Webtrees\Module\ModuleConfigInterface;
use Fisharebest\Webtrees\Module\ModuleConfigTrait;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Module\ModuleCustomTrait;
use Fisharebest\Webtrees\Module\ModuleGlobalInterface;
use Fisharebest\Webtrees\Module\ModuleGlobalTrait;
use Fisharebest\Webtrees\Services\HtmlService;
use Fisharebest\Webtrees\Services\TreeService;
use Fisharebest\Webtrees\Site;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\Validator;
use Fisharebest\Webtrees\View;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

use function count;
use function e;
use function file_exists;
use function in_array;
use function redirect;
use function view;

class TreesListModule extends HtmlBlockModule implements ModuleCustomInterface, ModuleBlockInterface, ModuleConfigInterface, ModuleGlobalInterface
{
    use ModuleBlockTrait;
    use ModuleCustomTrait;
    use ModuleConfigTrait;
    use ModuleGlobalTrait;

    public const CUSTOM_MODULE = 'hh-family-trees-list';
    public const CUSTOM_GITHUB_USER = 'hartenthaler';
    public const CUSTOM_WEBSITE = 'https://github.com/' . self::CUSTOM_GITHUB_USER . '/' . self::CUSTOM_MODULE . '/';

    private const DEFAULT_SORT = 'id_desc';
    private const DEFAULT_STYLE = 'list';
    private const DEFAULT_VISIBLE_FIELDS = 'families,individuals,events,surnames';
    private const TREE_PURPOSE_PREFERENCE = 'HH_FAMILY_TREES_PURPOSE';

    /** @var list<string> */
    private const OPTIONAL_FIELDS = ['purpose', 'families', 'individuals', 'events', 'surnames'];

    private TreeService $tree_service;

    public function __construct(HtmlService $html_service, TreeService $tree_service)
    {
        parent::__construct($html_service);

        $this->tree_service = $tree_service;
    }

    public function customModuleLatestVersionUrl(): string
    {
        return 'https://raw.githubusercontent.com/' . self::CUSTOM_GITHUB_USER . '/' . self::CUSTOM_MODULE . '/main/latest-version.txt';
    }

    public function resourcesFolder(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR;
    }

    public function boot(): void
    {
        View::registerNamespace($this->name(), $this->resourcesFolder() . 'views/');
        View::registerCustomView('::list', $this->name() . '::list');
        View::registerCustomView('::card', $this->name() . '::card');
        View::registerCustomView('::table', $this->name() . '::table');
        View::registerCustomView('::config', $this->name() . '::config');
        View::registerCustomView('::capsule', $this->name() . '::capsule');
        View::registerCustomView('::navbar', $this->name() . '::navbar');
    }

    public function headContent(): string
    {
        return '<link rel="stylesheet" href="' . e($this->assetUrl('css/treeslist.css')) . '">';
    }

    public function title(): string
    {
        /* I18N: Name of a module */
        return I18N::translate('Family tree list');
    }

    public function description(): string
    {
        return I18N::translate('List of family trees on this website');
    }

    public function customModuleAuthorName(): string
    {
        return 'Hermann Hartenthaler';
    }

    public function customModuleSupportUrl(): string
    {
        return self::CUSTOM_WEBSITE;
    }

    private function siteAllowsTreeList(): bool
    {
        return Site::getPreference('ALLOW_CHANGE_GEDCOM') === '1';
    }

    /**
     * Count the number of individuals in each tree.
     *
     * @return Collection<int,int>
     */
    private function totalIndividuals(): Collection
    {
        return DB::table('gedcom')
            ->leftJoin('individuals', 'i_file', '=', 'gedcom_id')
            ->groupBy(['gedcom_id'])
            ->pluck(new Expression('COUNT(i_id) AS aggregate'), 'gedcom_id')
            ->map(static fn (string $count): int => (int) $count);
    }

    /**
     * Count the number of families in each tree.
     *
     * @return Collection<int,int>
     */
    private function totalFamilies(): Collection
    {
        return DB::table('gedcom')
            ->leftJoin('families', 'f_file', '=', 'gedcom_id')
            ->groupBy(['gedcom_id'])
            ->pluck(new Expression('COUNT(f_id) AS aggregate'), 'gedcom_id')
            ->map(static fn (string $count): int => (int) $count);
    }

    /**
     * Count the number of events in each tree.
     *
     * @return array<int,int>
     */
    private function totalEvents(): array
    {
        $all_files = DB::table('gedcom')->select('gedcom_id')->distinct()->pluck('gedcom_id')->all();
        $event_counts = DB::table('dates')
            ->select('d_file as gedcom_id', DB::raw('COUNT(*) as count'))
            ->whereNotIn('d_fact', ['HEAD', 'CHAN'])
            ->groupBy('d_file')
            ->pluck('count', 'gedcom_id')
            ->all();

        $result = [];
        foreach ($all_files as $gedcom_id) {
            $result[(int) $gedcom_id] = (int) ($event_counts[$gedcom_id] ?? 0);
        }

        return $result;
    }

    /**
     * Count the number of surnames in each tree.
     *
     * @return Collection<int,int>
     */
    private function totalSurnames(): Collection
    {
        return DB::table('gedcom')
            ->leftJoin('name', 'n_file', '=', 'gedcom_id')
            ->groupBy(['gedcom_id'])
            ->pluck(new Expression('COUNT(DISTINCT n_surn) AS aggregate'), 'gedcom_id')
            ->map(static fn (string $count): int => (int) $count);
    }

    public function getBlock(Tree $tree, int $block_id, string $context, array $config = []): string
    {
        if (!$this->siteAllowsTreeList()) {
            return '';
        }

        $info_style = $this->getBlockSetting($block_id, 'infoStyle', self::DEFAULT_STYLE);
        $sort_style = $this->getBlockSetting($block_id, 'sortStyle', self::DEFAULT_SORT);
        $visible_fields = $this->visibleFields($this->getBlockSetting($block_id, 'visibleFields', self::DEFAULT_VISIBLE_FIELDS));
        if (!in_array($info_style, array_keys($this->infoStyles()), true)) {
            $info_style = self::DEFAULT_STYLE;
        }

        $sorted_trees = $this->tree_service->all();
        if ($sort_style === 'id_asc') {
            $sorted_trees = $sorted_trees->sortBy(static fn (Tree $tree): int => $tree->id());
        } else {
            $sorted_trees = $sorted_trees->sortByDesc(static fn (Tree $tree): int => $tree->id());
        }

        $content = view($info_style, [
            'block_id' => $block_id,
            'all_trees' => $sorted_trees->all(),
            'individuals' => in_array('individuals', $visible_fields, true) ? $this->totalIndividuals() : [],
            'families' => in_array('families', $visible_fields, true) ? $this->totalFamilies() : [],
            'events' => in_array('events', $visible_fields, true) ? $this->totalEvents() : [],
            'surnames' => in_array('surnames', $visible_fields, true) ? $this->totalSurnames() : [],
            'purposes' => in_array('purpose', $visible_fields, true) ? $this->treePurposeLabels($sorted_trees) : [],
            'visible_fields' => array_fill_keys($visible_fields, true),
            'treeicon' => $this->assetUrl('images/tree.png'),
            'familyicon' => $this->assetUrl('images/families.png'),
            'individualicon' => $this->assetUrl('images/person2.png'),
            'eventicon' => $this->assetUrl('images/event.png'),
            'surnameicon' => $this->assetUrl('images/sur.png'),
            'context' => $context,
        ]);

        if ($context !== ModuleBlockInterface::CONTEXT_EMBED) {
            $total_trees = $sorted_trees->count();
            $title = I18N::plural('There is one family tree on this website', 'This website has %d family trees', $total_trees, $total_trees);

            return view('modules/block-template', [
                'block' => Str::kebab($this->name()),
                'id' => $block_id,
                'config_url' => $this->configUrl($tree, $context, $block_id),
                'title' => $title,
                'content' => $content,
            ]);
        }

        return $content;
    }

    public function loadAjax(): bool
    {
        return false;
    }

    public function isUserBlock(): bool
    {
        return true;
    }

    public function isTreeBlock(): bool
    {
        return true;
    }

    public function customModuleVersion(): string
    {
        return '2.2.6.1';
    }

    public function saveBlockConfiguration(ServerRequestInterface $request, int $block_id): void
    {
        $info_style = Validator::parsedBody($request)->string('infoStyle', self::DEFAULT_STYLE);
        $sort_style = Validator::parsedBody($request)->string('sortStyle', self::DEFAULT_SORT);
        $params = (array) $request->getParsedBody();
        $visible_fields = array_values(array_intersect(self::OPTIONAL_FIELDS, array_keys((array) ($params['visibleFields'] ?? []))));

        if (!in_array($info_style, array_keys($this->infoStyles()), true)) {
            $info_style = self::DEFAULT_STYLE;
        }

        if (!in_array($sort_style, array_keys($this->sortStyles()), true)) {
            $sort_style = self::DEFAULT_SORT;
        }

        $this->setBlockSetting($block_id, 'infoStyle', $info_style);
        $this->setBlockSetting($block_id, 'sortStyle', $sort_style);
        $this->setBlockSetting($block_id, 'visibleFields', implode(',', $visible_fields));
    }

    public function editBlockConfiguration(Tree $tree, int $block_id): string
    {
        return view('config', [
            'info_style' => $this->getBlockSetting($block_id, 'infoStyle', self::DEFAULT_STYLE),
            'info_styles' => $this->infoStyles(),
            'sort_style' => $this->getBlockSetting($block_id, 'sortStyle', self::DEFAULT_SORT),
            'sort_styles' => $this->sortStyles(),
            'field_options' => $this->fieldOptions(),
            'visible_fields' => array_fill_keys($this->visibleFields($this->getBlockSetting($block_id, 'visibleFields', self::DEFAULT_VISIBLE_FIELDS)), true),
        ]);
    }

    public function getAdminAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->layout = 'layouts/administration';

        return $this->viewResponse($this->name() . '::settings', [
            'title' => $this->title(),
            'trees' => $this->tree_service->all()->sortBy(static fn (Tree $tree): string => $tree->title())->all(),
            'purpose_options' => ['' => I18N::translate('Not specified')] + $this->purposeOptions(),
            'purpose_preference' => self::TREE_PURPOSE_PREFERENCE,
        ]);
    }

    public function postAdminAction(ServerRequestInterface $request): ResponseInterface
    {
        $params = (array) $request->getParsedBody();
        $submitted = (array) ($params['purpose'] ?? []);
        $valid = array_keys($this->purposeOptions());

        foreach ($this->tree_service->all() as $tree) {
            $purpose = (string) ($submitted[$tree->id()] ?? '');
            $tree->setPreference(self::TREE_PURPOSE_PREFERENCE, in_array($purpose, $valid, true) ? $purpose : '');
        }

        FlashMessages::addMessage(I18N::translate('The research purposes of the family trees have been updated.'), 'success');

        return redirect($this->getConfigLink());
    }

    /** @return list<string> */
    private function visibleFields(string $stored): array
    {
        return array_values(array_intersect(self::OPTIONAL_FIELDS, explode(',', $stored)));
    }

    /** @return array<string,string> */
    private function fieldOptions(): array
    {
        return [
            'purpose' => I18N::translate('Research purpose'),
            'families' => MoreI18N::xlate('Families'),
            'individuals' => MoreI18N::xlate('Individuals'),
            'events' => MoreI18N::xlate('Events'),
            'surnames' => MoreI18N::xlate('Surnames'),
        ];
    }

    /** @return array<string,string> */
    private function purposeOptions(): array
    {
        return [
            'ResearchFamily' => I18N::translate('Family and ancestry research'),
            'ResearchOns' => I18N::translate('One-name study'),
            'ResearchPlace' => I18N::translate('One-place study or local family book'),
            'ResearchFarm' => I18N::translate('Farm and farmstead research'),
            'ResearchTopic' => I18N::translate('Thematic research'),
            'ResearchMigration' => I18N::translate('Migration research'),
            'ResearchCommunity' => I18N::translate('Community research'),
            'ResearchTest' => I18N::translate('Test'),
        ];
    }

    /**
     * Return the configured research purpose for a family tree in the current user language.
     */
    public function researchPurpose(Tree $tree): string
    {
        return $this->purposeOptions()[$tree->getPreference(self::TREE_PURPOSE_PREFERENCE)] ?? '';
    }

    /**
     * @param Collection<int,Tree> $trees
     * @return array<int,string>
     */
    private function treePurposeLabels(Collection $trees): array
    {
        $options = $this->purposeOptions();
        $labels = [];
        foreach ($trees as $tree) {
            $labels[$tree->id()] = $options[$tree->getPreference(self::TREE_PURPOSE_PREFERENCE)] ?? '';
        }

        return $labels;
    }

    /**
     * @return array<string,string>
     */
    private function infoStyles(): array
    {
        return [
            'list' => I18N::translate('list'),
            'table' => I18N::translate('table'),
            'card' => I18N::translate('card'),
            'capsule' => I18N::translate('capsule'),
            'navbar' => I18N::translate('navbar'),
        ];
    }

    /**
     * @return array<string,string>
     */
    private function sortStyles(): array
    {
        return [
            'id_asc' => I18N::translate('sort by internal tree number, oldest first'),
            'id_desc' => I18N::translate('sort by internal tree number, newest first'),
        ];
    }

    public function customTranslations(string $language): array
    {
        $file = $this->resourcesFolder() . 'lang' . DIRECTORY_SEPARATOR . $language . '.mo';

        return file_exists($file) ? (new Translation($file))->asArray() : [];
    }
}
