<?php
// Run each version in a separate process: PHP constants and classes cannot be unloaded.
if ($argc === 1) {
	foreach (['7.0', '7.4', '8.0'] as $version) {
		passthru(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.$version, $status);
		if ($status !== 0) {
			exit($status);
		}
	}
	exit(0);
}

if (in_array($argv[1], ['7.4', '8.0'], true)) {
	class CWidgetFormView {
		public function initFormJs() {}
	}
}
else {
	class CWidgetFormView {}
	define('ZBX_STYLE_COLOR_PICKER', 'color-picker');
}

class CFormGrid {
	public const ZBX_STYLE_FIELDS_GROUP_LABEL = 'fields-group-label';
	public const ZBX_STYLE_FIELDS_GROUP = 'fields-group';
}

class StubFormBase {}
class_alias(StubFormBase::class, 'Zabbix\Widgets\CWidgetForm');

class LegacyColorField {
	public function __construct(public string $name, public string $label) {}
}
class ModernColorField extends LegacyColorField {
	public bool $inherited = false;
	public function allowInherited(): self {
		$this->inherited = true;
		return $this;
	}
}
$modern = in_array($argv[1], ['7.4', '8.0'], true);
class_alias($modern ? ModernColorField::class : LegacyColorField::class,
	'Zabbix\Widgets\Fields\CWidgetFieldColor'
);
require __DIR__.'/../includes/WidgetForm.php';
$form = new Modules\Honeycomb_with_filter\Includes\WidgetForm();
$make_color = new ReflectionMethod($form, 'makeColorField');
$make_color->setAccessible(true);
foreach (['primary_label_color', 'secondary_label_color', 'bg_color'] as $name) {
	$field = $make_color->invoke($form, $name, 'Color');
	if ($field->name !== $name || ($modern && !$field->inherited)) {
		throw new RuntimeException($argv[1].': inherited color '.$name);
	}
}

// Do not define CWidgetFieldColumnsList: palette access must work without it.
class StubWidgetBase {}
class_alias(StubWidgetBase::class, 'Zabbix\Core\CWidget');
require __DIR__.'/../Widget.php';
$palette = Modules\Honeycomb_with_filter\Widget::DEFAULT_COLOR_PALETTE;
if (count($palette) !== 20 || $palette[0] !== 'FF465C') {
	throw new RuntimeException('Unexpected default palette');
}
foreach ($palette as $color) {
	if (!preg_match('/^[0-9A-F]{6}$/D', $color)) {
		throw new RuntimeException('Invalid palette color');
	}
}
$view_source = file_get_contents(__DIR__.'/../views/widget.edit.php');
if (str_contains($view_source, 'CWidgetFieldColumnsList')
		|| !str_contains($view_source, 'Widget::DEFAULT_COLOR_PALETTE')) {
	throw new RuntimeException('Edit view must use the module palette');
}

ob_start();
include __DIR__.'/../views/widget.edit.js.php';
$javascript = ob_get_clean();

$modern = in_array($argv[1], ['7.4', '8.0'], true);
$checks = [
	'form lifecycle' => str_contains($javascript, 'extends CWidgetForm') === $modern,
	'ready event' => str_contains($javascript, 'this.ready()') === $modern,
	'form lookup' => str_contains($javascript, 'this.getForm()') === $modern,
	'legacy color picker' => str_contains($javascript, '$(colorpicker).colorpicker(') === !$modern,
	'global form object' => str_contains($javascript, 'window.widget_form =') === $modern,
	'legacy form object' => str_contains($javascript, 'window.widget_honeycomb_form =') === !$modern,
	'PHP fully rendered' => !str_contains($javascript, '<?')
];

foreach ($checks as $name => $passed) {
	if (!$passed) {
		throw new RuntimeException($argv[1].': '.$name);
	}
}
echo 'PASS: '.$argv[1]." form rendering and lifecycle selection\n";
