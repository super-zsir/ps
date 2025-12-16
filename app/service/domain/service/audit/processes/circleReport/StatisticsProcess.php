<?php


namespace Imee\Service\Domain\Service\Audit\Processes\CircleReport;


use Imee\Models\Bms\XsstKefuTaskCirclereport;

class StatisticsProcess
{
	public function handle()
	{
		$undo = XsstKefuTaskCirclereport::find([
			'columns' => 'choice, count(id) as undo',
			'group' => 'choice'
		])->toArray();
		return [
			'undo' => array_column($undo, null, 'choice')
		];
	}
}