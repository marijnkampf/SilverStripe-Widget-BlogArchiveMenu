<?php
/**
 * Shows a widget with blog archive menu
 */
class BlogArchiveMenuWidget extends Widget {
	static $db = array(
		'WidgetTitle' => "Text",
		'ShowChildren' => 'Boolean',
		'ShowLastYears' => 'Int',
	);
	
	static $has_one = array();
	
	static $has_many = array();
	
	static $many_many = array();
	
	static $belongs_many_many = array();
	
	static $defaults = array(
		'ShowChildren' => 'true',
		'DisplayMode' => 'month'
	);
	
	static $cmsTitle = 'Blog Archive Menu';
	static $WidgetTitle;
	
	static $description = 'Show blog achive with sub pages.';
	
	function getCMSFields() {
		$fields = parent::getCMSFields(); 
		
		$fields->merge( 
			new FieldSet(
				new TextField("WidgetTitle", 'Custom title for widget'),
			  new NumericField('ShowLastYears', 'Nr of years to show all months for'),
			  new CheckboxField('ShowChildren', 'Show blog entries for selected date')
			)	
		);
		
		$this->extend('updateCMSFields', $fields);
		
		return $fields;
	}

	function Title() {
		return ($this->WidgetTitle) ? $this->WidgetTitle : 'Browse by date';
	}
	
	function Dates() {
		Requirements::themedCSS('archivewidget');
	
		$results = new DataObjectSet();
		$container = BlogTree::current();
		$ids = $container->BlogHolderIDs();
		
		$stage = Versioned::current_stage();
		$suffix = (!$stage || $stage == 'Stage') ? "" : "_$stage";

		$monthclause = method_exists(DB::getConn(), 'formattedDatetimeClause') ? DB::getConn()->formattedDatetimeClause('"Date"', '%m') : 'MONTH("Date")';
		$yearclause  = method_exists(DB::getConn(), 'formattedDatetimeClause') ? DB::getConn()->formattedDatetimeClause('"Date"', '%Y') : 'YEAR("Date")';

		$sqlResults = DB::query("
			SELECT DISTINCT CAST($monthclause AS " . DB::getConn()->dbDataType('unsigned integer') . ") AS \"Month\", $yearclause AS \"Year\"
			FROM \"SiteTree$suffix\" INNER JOIN \"BlogEntry$suffix\" ON \"SiteTree$suffix\".\"ID\" = \"BlogEntry$suffix\".\"ID\"
			WHERE \"ParentID\" IN (" . implode(', ', $ids) . ")
			ORDER BY \"Year\" DESC, \"Month\" DESC;"
		);

		if ($this->ShowLastYears == 0) $cutOffYear = 0;
		else $cutOffYear = (int)date("Y") - $this->ShowLastYears;
		
		$years = array();

		if (Director::get_current_page()->ClassName == 'BlogHolder') {
			$urlParams = Director::urlParams();
			$yearParam = $urlParams['ID'];
			$monthParam = $urlParams['OtherID'];				
		} else {
			$date = new DateTime(Director::get_current_page()->Date);
			$yearParam = $date->format("Y");
			$monthParam = $date->format("m");				
		}

		if($sqlResults) foreach($sqlResults as $sqlResult) {
			$isMonthDisplay = true;

			$year = ($sqlResult['Year']) ? (int) $sqlResult['Year'] : date('Y');
			$isMonthDisplay = ($year > $cutOffYear);// $dateFormat = 'Month'; else $dateFormat = 'Year';
			
			$monthVal = (isset($sqlResult['Month'])) ? (int) $sqlResult['Month'] : 1;
			$month = ($isMonthDisplay) ? $monthVal : 1;

			$date = DBField::create('Date', array(
				'Day' => 1,
				'Month' => $month,
				'Year' => $year
			));
			
			if($isMonthDisplay) {
				$link = $container->Link('date') . '/' . $sqlResult['Year'] . '/' . sprintf("%'02d", $monthVal);
			} else {
				$link = $container->Link('date') . '/' . $sqlResult['Year'];
			}
			
			if (($isMonthDisplay) || (!$isMonthDisplay && !in_array($year, $years))) {
				$years[] = $year;
				$current = false;
				
				$LinkingMode = "link";
				if (($isMonthDisplay && ($yearParam == $year) && ($monthParam == $month)) || (!$isMonthDisplay && ($yearParam == $year)))  {
					$LinkingMode = "current";
					$current = true;

					if (($this->ShowChildren) && ($isMonthDisplay)) {					
						$filter = $yearclause . ' = ' . $year . ' AND ' . $monthclause . ' = ' . $month;
						$children = DataObject::get('BlogEntry', $filter, "Date DESC");
					}
				}
				
				$results->push(new ArrayData(array(
					'Date' => $date,
					'Year' => $year,
					'Link' => $link,
					'NoMonth' => !$isMonthDisplay,
					'LinkingMode' => $LinkingMode,
					'Children' => $children
				)));
				unset($children);
			}
		}
		return $results;
	}
}

