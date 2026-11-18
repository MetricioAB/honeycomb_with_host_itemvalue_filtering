<?php declare(strict_types = 0);
/*
** Copyright (C) 2001-2026 Zabbix SIA
**
** This program is free software: you can redistribute it and/or modify it under the terms of
** the GNU Affero General Public License as published by the Free Software Foundation, version 3.
**
** This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
** without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
** See the GNU Affero General Public License for more details.
**
** You should have received a copy of the GNU Affero General Public License along with this program.
** If not, see <https://www.gnu.org/licenses/>.
**/


namespace Modules\Honeycomb_with_filter;

use Zabbix\Core\CWidget;

class Widget extends CWidget {

	// Keep the original palette local: CWidgetFieldColumnsList is not available in all supported versions.
	public const DEFAULT_COLOR_PALETTE = [
		'FF465C', 'FFD54F', '0EC9AC', '524BBC', 'ED1248', 'D1E754', '2AB5FF', '385CC7', 'EC1594', 'BAE37D',
		'6AC8FF', 'EE2B29', '3CA20D', '6F4BBC', '00A1FF', 'F3601B', '1CAE59', '45CFDB', '894BBC', '6D6D6D'
	];

	public function getDefaultName(): string {
		return _('Honeycomb_with_filter');
	}

	public function getTranslationStrings(): array {
		return [
			'class.svghoneycomb.js' => [
				'No data' => _('No data')
			],
			'class.widget.js' => [
				'Actions' => _('Actions'),
				'Download image' => _('Download image')
			]
		];
	}
}
