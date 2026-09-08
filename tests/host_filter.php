<?php
// Standalone regression tests: php tests/host_filter.php
define('ITEM_STATUS_ACTIVE', 0);
define('ITEM_VALUE_TYPE_FLOAT', 0);
define('ITEM_VALUE_TYPE_STR', 1);
define('ITEM_VALUE_TYPE_LOG', 2);
define('ITEM_VALUE_TYPE_UINT64', 3);
define('ITEM_VALUE_TYPE_TEXT', 4);

class CControllerDashboardWidgetView {
	public array $fields_values = [];
	public bool $template = false;
	public function isTemplateDashboard(): bool { return $this->template; }
}

class API {
	public static array $items = [];
	public static array $queries = [];
	public static function Item(): self { return new self(); }
	public function get(array $options): array {
		self::$queries[] = $options;
		return self::$items;
	}
}

class Manager {
	public static array $values = [];
	public static function History(): self { return new self(); }
	public function getLastValues(array $items, int $limit, int $period): array {
		return array_intersect_key(self::$values, array_flip(array_column($items, 'itemid')));
	}
}

class CSettingsHelper {
	public const HISTORY_PERIOD = 'history_period';
	public static function get(string $key): string { return '3600'; }
}

function timeUnitToSeconds(string $value): int { return (int) $value; }

require __DIR__.'/../actions/WidgetView.php';

$widget = new Modules\Honeycomb_with_filter\Actions\WidgetView();
$method = new ReflectionMethod($widget, 'filterHostItems');
$method->setAccessible(true);
$cells = [
	['itemid' => '11', 'hostid' => '1'],
	['itemid' => '12', 'hostid' => '1'],
	['itemid' => '21', 'hostid' => '2']
];
API::$items = [
	['itemid' => '101', 'hostid' => '1', 'value_type' => ITEM_VALUE_TYPE_UINT64],
	['itemid' => '201', 'hostid' => '2', 'value_type' => ITEM_VALUE_TYPE_UINT64]
];
Manager::$values = ['101' => [['value' => '0']], '201' => [['value' => '1']]];

function check(bool $condition, string $message): void {
	if (!$condition) {
		throw new RuntimeException($message);
	}
	echo "PASS: $message\n";
}

check($method->invoke($widget, $cells) === $cells && API::$queries === [],
	'Existing widgets without filter settings retain all cells without additional queries');
$widget->fields_values = ['host_filter_items' => ['Status'], 'host_filter_value' => '0'];
check($method->invoke($widget, $cells) === [$cells[2]], 'Value 0 removes every cell from only its host');
check(API::$queries[0]['hostids'] === ['1', '2'], 'Lookup is restricted to candidate hosts');
check(API::$queries[0]['search'] === ['name_resolved' => ['Status']], 'Selected item names drive lookup');
check(!isset(API::$queries[0]['tags']), 'Filter item does not need display item tags');

Manager::$values['101'][0]['value'] = '0.000';
check($method->invoke($widget, $cells) === [$cells[2]], 'Numeric zero matches decimal zero');
API::$items[0]['value_type'] = ITEM_VALUE_TYPE_TEXT;
check($method->invoke($widget, $cells) === $cells, 'Text values use exact equality');
$widget->fields_values['host_filter_value'] = 'offline';
Manager::$values['101'][0]['value'] = 'offline';
check($method->invoke($widget, $cells) === [$cells[2]], 'Text status can exclude a host');
Manager::$values = [];
check($method->invoke($widget, $cells) === $cells, 'Missing recent history retains hosts');
API::$items = [];
check($method->invoke($widget, $cells) === $cells, 'Missing filter items retain hosts');

$widget->template = true;
$method->invoke($widget, $cells);
check(end(API::$queries)['search'] === ['name' => ['Status']], 'Template dashboards use unresolved item names');
$widget->fields_values['host_filter_items'] = ['*'];
$method->invoke($widget, $cells);
check(end(API::$queries)['search'] === ['name' => null], 'Wildcard selects all supported filter items');
$widget->fields_values['host_filter_items'] = [];
check($method->invoke($widget, $cells) === $cells, 'Empty selection disables filtering');
check($method->invoke($widget, []) === [], 'Empty candidate set remains empty');
