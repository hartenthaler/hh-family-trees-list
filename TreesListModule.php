<?php

declare(strict_types=1);

namespace TreesListModule;

use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Module\HtmlBlockModule;
use Fisharebest\Webtrees\Module\ModuleBlockInterface;
use Fisharebest\Webtrees\Module\ModuleBlockTrait;
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

use function count;
use function e;
use function in_array;
use function view;

class TreesListModule extends HtmlBlockModule implements ModuleCustomInterface, ModuleBlockInterface, ModuleGlobalInterface
{
    use ModuleBlockTrait;
    use ModuleCustomTrait;
    use ModuleGlobalTrait;

    public const CUSTOM_MODULE = 'hh-family-trees-list';
    public const CUSTOM_GITHUB_USER = 'hartenthaler';
    public const CUSTOM_WEBSITE = 'https://github.com/' . self::CUSTOM_GITHUB_USER . '/' . self::CUSTOM_MODULE . '/';

    private const DEFAULT_SORT = 'id_desc';
    private const DEFAULT_STYLE = 'list';

    private TreeService $tree_service;

    public function __construct(HtmlService $html_service, TreeService $tree_service)
    {
        parent::__construct($html_service);

        $this->tree_service = $tree_service;
    }

    public function customModuleLatestVersionUrl(): string
    {
        return 'https://raw.githubusercontent.com/' . self::CUSTOM_GITHUB_USER . '/' . self::CUSTOM_MODULE . '/main/version.txt';
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
            'individuals' => $this->totalIndividuals(),
            'families' => $this->totalFamilies(),
            'events' => $this->totalEvents(),
            'surnames' => $this->totalSurnames(),
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

        if (!in_array($info_style, array_keys($this->infoStyles()), true)) {
            $info_style = self::DEFAULT_STYLE;
        }

        if (!in_array($sort_style, array_keys($this->sortStyles()), true)) {
            $sort_style = self::DEFAULT_SORT;
        }

        $this->setBlockSetting($block_id, 'infoStyle', $info_style);
        $this->setBlockSetting($block_id, 'sortStyle', $sort_style);
    }

    public function editBlockConfiguration(Tree $tree, int $block_id): string
    {
        return view('config', [
            'info_style' => $this->getBlockSetting($block_id, 'infoStyle', self::DEFAULT_STYLE),
            'info_styles' => $this->infoStyles(),
            'sort_style' => $this->getBlockSetting($block_id, 'sortStyle', self::DEFAULT_SORT),
            'sort_styles' => $this->sortStyles(),
        ]);
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
        return match ($language) {
            'de' => $this->germanTranslations(),
            'nl' => $this->dutchTranslations(),
            'zh-Hans' => $this->hansTranslations(),
            'zh-Hant' => $this->hantTranslations(),
            default => [],
        };
    }

    /**
     * @return array<string,string>
     */
    protected function germanTranslations(): array
    {
        return [
            'There is one family tree on this website' . I18N::PLURAL . 'This website has %d family trees' => 'Es gibt einen Stammbaum auf dieser Website' . I18N::PLURAL . 'Diese Website hat %d Stammbäume',
            'Family tree list' => 'Stammbaum-Liste',
            'List of family trees on this website' => 'Stammbaumliste der Website',
            'No family trees can be shown.' => 'Es können keine Stammbäume angezeigt werden.',
            'Events' => 'Ereignisse',
            'navbar' => 'Navigationsleiste',
            'card' => 'Karten',
            'capsule' => 'Plaketten',
            'sort by internal tree number, oldest first' => 'Sortieren nach interner Stammbaum-Nummer, älteste zuerst',
            'sort by internal tree number, newest first' => 'Sortieren nach interner Stammbaum-Nummer, neueste zuerst',
            '*Click on the header to sort the values.' => '*Klicken Sie auf die Kopfzeile, um die Werte zu sortieren.',
        ];
    }

    /**
     * @return array<string,string>
     */
    protected function dutchTranslations(): array
    {
        return [
            'There is one family tree on this website' . I18N::PLURAL . 'This website has %d family trees' => 'Deze website heeft één stamboom' . I18N::PLURAL . 'Deze website heeft %d stambomen',
            'Family tree list' => 'Stamboomlijst',
            'List of family trees on this website' => 'Lijst van stambomen op website',
            'No family trees can be shown.' => 'Er kunnen geen stambomen worden getoond.',
            'table' => 'tabel',
            'card' => 'kaarten',
            'capsule' => 'labels',
            'navbar' => 'navigatiebalk',
            'sort by internal tree number, oldest first' => 'sorteren op intern stamboomnummer, oudste eerst',
            'sort by internal tree number, newest first' => 'sorteren op intern stamboomnummer, nieuwste eerst',
            '*Click on the header to sort the values.' => '*Klik op de koptekst om de waarden te sorteren',
        ];
    }

    /**
     * @return array<string,string>
     */
    protected function hansTranslations(): array
    {
        return [
            'There is one family tree on this website' . I18N::PLURAL . 'This website has %d family trees' => '本网站已收录%d部家谱',
            'Family tree list' => '家谱列表',
            'List of family trees on this website' => '显示网站上的家谱列表',
            'No family trees can be shown.' => '没有可显示的家谱。',
            'list' => '列  表',
            'table' => '表  格',
            'card' => '卡  片',
            'capsule' => '胶  囊',
            'navbar' => '导航栏',
            'sort by internal tree number, oldest first' => '按内部家谱编号排序，正序',
            'sort by internal tree number, newest first' => '按内部家谱编号排序，倒序',
            '*Click on the header to sort the values.' => '*点击表头可对数值进行排序。',
        ];
    }

    /**
     * @return array<string,string>
     */
    protected function hantTranslations(): array
    {
        return [
            'There is one family tree on this website' . I18N::PLURAL . 'This website has %d family trees' => '本網站已收錄%d部家譜',
            'Family tree list' => '家譜列表',
            'List of family trees on this website' => '顯示網站上的家譜列表',
            'No family trees can be shown.' => '沒有可顯示的家譜。',
            'list' => '列  表',
            'table' => '表  格',
            'card' => '卡  片',
            'capsule' => '膠  囊',
            'navbar' => '導航欄',
            'sort by internal tree number, oldest first' => '按內部家譜編號排序，最老優先',
            'sort by internal tree number, newest first' => '按內部家譜編號排序，最新優先',
            '*Click on the header to sort the values.' => '*點擊表頭可對數值進行排序。',
        ];
    }
}
