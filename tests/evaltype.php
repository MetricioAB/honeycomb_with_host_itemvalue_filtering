<?php
// Standalone API-argument regression test: php tests/evaltype.php
define('TAG_EVAL_TYPE_AND_OR', 0);
define('TAG_EVAL_TYPE_OR', 2);
define('HOST_MAINTENANCE_STATUS_OFF', 0);
define('ITEM_STATUS_ACTIVE', 0);

class CControllerDashboardWidgetView {
	public array $fields_values = [];
	public bool $template = false;
	public function isTemplateDashboard(): bool { return $this->template; }
}

class API {
	public static array $queries = [];
	private string $type;
	public function __construct(string $type) { $this->type = $type; }
	public static function Host(): self { return new self('host'); }
	public static function Item(): self { return new self('item'); }
	public function get(array $options): array {
		if (!is_int($options['evaltype'])) {
			throw new RuntimeException($this->type.': evaltype must be an integer');
		}
		self::$queries[$this->type] = $options;
		return $this->type === 'host' ? [1 => ['hostid' => '1']] : [];
	}
}

require __DIR__.'/../actions/WidgetView.php';
$widget = new Modules\Honeycomb_with_filter\Actions\WidgetView();
$get_cells = new ReflectionMethod($widget, 'getCells');
$get_cells->setAccessible(true);

foreach ([false, true] as $template) {
	foreach ([null, '0', '2'] as $evaltype) {
		$widget->template = $template;
		$widget->fields_values = [
			'hostids' => ['1'], 'groupids' => [], 'maintenance' => 0,
			'host_tags' => $evaltype === null ? [] : [['tag' => 'env', 'value' => 'test']],
			'item_tags' => [], 'items' => ['*']
		];
		if ($evaltype !== null) {
			$widget->fields_values['evaltype_host'] = $evaltype;
			$widget->fields_values['evaltype_item'] = $evaltype;
		}
		$get_cells->invoke($widget, 10);
		$expected_host = $template || $evaltype === null ? TAG_EVAL_TYPE_AND_OR : (int) $evaltype;
		if (API::$queries['host']['evaltype'] !== $expected_host
				|| API::$queries['item']['evaltype'] !== (int) ($evaltype ?? TAG_EVAL_TYPE_AND_OR)) {
			throw new RuntimeException('Incorrect tag evaluation mode');
		}
	}
}
echo "PASS: Integer evaltype for hosts and items, with/without tags and on template dashboards\n";
