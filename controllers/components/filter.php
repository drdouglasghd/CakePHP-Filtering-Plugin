<?php
// file: /app/controllers/components/filter.php
/**
 * Filter component
 *
 * @original concept by Nik Chankov - http://nik.chankov.net
 * @modified and extended by Maciej Grajcarek - http://blog.uplevel.pl
 * @modified again by James Fairhurst - http://www.jamesfairhurst.co.uk
 * @modified again by Geoff Douglas - http://www.neverbehind.com
 * @version 0.1
 */
class FilterComponent extends Object {
    /**
     * fields which will replace the regular syntax in where i.e. field = 'value'
     */
    var $fieldFormatting = array(
		"string"	=> "LIKE '%%%s%%'",
		"text"		=> "LIKE '%%%s%%'",
		"date"		=> "LIKE '%%%s%%'",
		"datetime"	=> "LIKE '%%%s%%'"
	);

	var $likeFields = array(
		"string"	=> "LIKE '%%%s%%'",
		"text"	=> "LIKE '%%%s%%'",
		"date"	=> "LIKE '%%%s%%'",
		"datetime"=> "LIKE '%%%s%%'"
	);

	/**
	 * Paginator params sent in URL
	 */
   	var $paginatorParams = array(
		'page',
		'sort',
		'direction'
   	);

   	/**
   	 *  Url variable used in paginate helper (array('url'=>$url));
   	 */
   	var $url = array();
	var $urlHelper = array();

    /**
     * Function which will change controller->data array
     * @param object $controller the class of the controller which call this component
     * @param array $whiteList contains list of allowed filter attributes
     * @access public
     */
	function process($controller, $whiteList = null){
        $controller = $this->_prepareFilter($controller);
        $ret = array();
        if(isset($controller->data)){
			//pr($controller->data);
            // loop models
            foreach($controller->data as $key=>$value) {
				if(is_array($value) && !empty($value)){
					foreach($value as $k=>$v) {
						//pr($v);
						if(is_array($v) && !empty($v)){
							foreach($v as $kk => $vv){
								//pr('VV'.$vv);
								if(empty($vv)){ unset($controller->data[$key][$k][$kk]); unset($v[$kk]);}
							}
						}
						if(empty($v) && $v != 0) { unset($controller->data[$key][$k]); unset($value[$k]);}
					}
				} 
				if(empty($value)){ unset($controller->data[$key]);}
			}			
            foreach($controller->data as $key=>$value) {
				// get fieldnames from database of model
				$columns = array();
				//pr($controller->{$controller->modelClass}->hasOne[$key]);
                if(isset($controller->{$key})) {
                    $columns = $controller->{$key}->getColumnTypes();
				} elseif (isset($controller->{$controller->modelClass}->belongsTo[$key])) {
                    $columns = $controller->{$controller->modelClass}->{$key}->getColumnTypes();
				} elseif (isset($controller->{$controller->modelClass}->hasOne[$key])) {
                    $columns = $controller->{$controller->modelClass}->{$key}->getColumnTypes();
				}
				//pr($columns);
				// if columns exist
				if(!empty($columns)) {
					//pr($value);
					// loop through filter data
					foreach($value as $k=>$v) {
						if(is_array($v) && count(array_filter($v)) == 1 && isset($v[$k])) $v = $v[$k];
						// JF: deal with datetime filter
						if(isset($columns[$k]) && ($columns[$k]=='datetime' || $columns[$k]=='date')) {
							//pr($k.'=>'.$v);
							//pr(preg_match('/start_date (?P<startdate>\d{4}-\d{2}-\d{2}) end_date (?P<enddate>\d{4}-\d{2}-\d{2})/',$v,$match));
							//pr($match);
							if(is_string($v) && preg_match('/start_date (?P<startdate>\d{4}-\d{2}-\d{2}) end_date (?P<enddate>\d{4}-\d{2}-\d{2})/',$v,$match)){
								//pr($v);
								//$fieldArr = preg_split('/\ /',$v);
								//pr($fieldArr);
								if(isset($match['startdate']) && isset($match['enddate'])){
									$v = array('start_date'=>$match['startdate'],'end_date'=>$match['enddate']);
									$controller->data[$key][$k] = $v;
									//pr($controller->data);	
								}
								
								//$fieldArr = preg_split('/\ /',$v);
								//pr($fieldArr);
								//if($fieldArr[0] == 'start_date' && $fieldArr[2] == 'end_date'){
								//	$v = array($fieldArr[0]=>$fieldArr[1],$fieldArr[2]=>$fieldArr[3]);
								//	$controller->data[$key][$k] = array($fieldArr[0]=>$fieldArr[1],$fieldArr[2]=>$fieldArr[3]);				
								//} 
							}
						
							$v = $this->_prepare_datetime($v);
						}

							// if filter value has been entered
					    if($v != '') {
									// if filter is in whitelist
							if(is_array($whiteList) && !in_array($k,$whiteList) ){
								continue;
							}
                        	
							$rex = '/(?P<andor>(OR|AND|))(?P<operatr>(<>|<=|>=|<|>|=|))(?P<fldvalue>.*)/';
							if(is_string($v) && isset($columns[$k]) && preg_match_all($rex,$v,$match)){
								$v = str_replace(array('AND ','OR ','<> ','<= ','>= ','< ','> ','= '),array('AND','OR','<>','<=','>=','<','>','='),$v);
								foreach(explode(' ',trim($v)) as $vs){
									//$rex = '/(?P<operatr>(<>|<=|>=|<|>))(?P<fldvalue>.*)/'; //|<=|>=|<|>
									//pr(preg_match_all($rex,$vs,$matches));
									preg_match_all($rex,$vs,$matches);
									if(empty($matches['operatr'])){
										$matches['operatr'][0] = '=';
										$matches['fldvalue'][0] = trim($vs);
									}
									if(empty($matches['operatr'][0])){
										$matches['operatr'][0] = '=';
										$matches['fldvalue'][0] = trim($vs);
									}

									//pr($matches);
									
									//pr($likeFields[$columns[$k]]);
									if(isset($this->likeFields[$columns[$k]]) && $matches['operatr'][0] == '<>'){
										$matches['operatr'][0] = 'NOT LIKE';
										$matches['fldvalue'][0] = sprintf('%%%s%%',trim($matches['fldvalue'][0]));
									}
									if(isset($this->likeFields[$columns[$k]]) && $matches['operatr'][0] == '='){
										$matches['operatr'][0] = 'LIKE';
										$matches['fldvalue'][0] = sprintf('%%%s%%',trim($matches['fldvalue'][0]));
									}
									if(!empty($matches['andor']) && $matches['andor'][0] == 'OR'){
										$ret['OR'][] = sprintf("%s.%s %s '%s'",$key,$k,$matches['operatr'][0],trim($matches['fldvalue'][0]));
										$controller->data['FilterExpl'][] = sprintf("OR %s.%s %s '%s'",$key,$k,$matches['operatr'][0],trim($matches['fldvalue'][0]));										
									} else {
										$fieldValue = trim($matches['fldvalue'][0]);
											$ret[] = sprintf("%s.%s %s '%s'",$key,$k,$matches['operatr'][0],trim($matches['fldvalue'][0]));
											$controller->data['FilterExpl'][] = sprintf("%s.%s %s '%s'",$key,$k,$matches['operatr'][0],trim($matches['fldvalue'][0]));
									}
								}
									// check if there are some fieldFormatting set
							}elseif(isset($columns[$k]) && isset($this->fieldFormatting[$columns[$k]])) {
								
								if(is_array($v) && ($columns[$k] == 'datetime' || $columns[$k] == 'date')){
									if(!isset($v['end_date'])) $v['end_date'] = $v['start_date'];
									$ret[] = sprintf("%s.%s BETWEEN '%s' AND '%s'",$key,$k,$v['start_date'],$v['end_date']);
									$controller->data['FilterExpl'][] = sprintf("%s.%s BETWEEN '%s' AND '%s'",$key,$k,$v['start_date'],$v['end_date']);
									$v = sprintf('start_date %s end_date %s',$v['start_date'],$v['end_date']);
								} else {								
									// insert value into fieldFormatting
									//$v=str_replace(" ", "%", $v);
									$tmp = sprintf($this->fieldFormatting[$columns[$k]], $v);
									// don't put key.fieldname as array key if a LIKE clause
									if (substr($tmp,0,4)=='LIKE') {
										if (!(strpos($v, " ")===false)){
											$searchPhase = explode(' ', $v);
											foreach($searchPhase as $word){
												$tmp = sprintf($this->fieldFormatting[$columns[$k]], $word);
												//$ret[] = sprintf('%s.%s %s',$key,$k,$tmp); //ori
												$ret[] = sprintf('%s.%s %s',$key,$k,$tmp);
												$controller->data['FilterExpl'][] = sprintf('%s.%s %s',$key,$k,$tmp);
											}
										} else {
											$ret[] = sprintf('%s.%s %s',$key,$k,$tmp);
											$controller->data['FilterExpl'][] = sprintf('%s.%s %s',$key,$k,$tmp);
										}
									} else {
										$ret[$key.'.'.$k] = $tmp;
										$controller->data['FilterExpl'][] = sprintf('%s.%s %s',$key,$k,$tmp);
									}
									//pr ($ret);
								}
							} elseif(isset($columns[$k])) {
								$rex = '/(< |> |<= |>= |<> )/';
								//$rex = '/(<|>|<=|>=|<>)/';
								
								if(preg_match($rex,$v,$matches)){
									//pr($matches);
									//$v = preg_replace('/\ /','',$v);
									$split = preg_split($rex,$v);
									$oper = explode(' ',$v);
									$ret[] = sprintf('%s.%s %s %s',$key,$k,$oper[0],$split[1]);
									$controller->data['FilterExpl'][] = sprintf('%s.%s %s %s',$key,$k,$oper[0],$split[1]);					
								} else {
									// build up where clause with field and value
									$ret[$key.'.'.$k] = $v;
									$controller->data['FilterExpl'][] = sprintf('%s.%s %s %s',$key,$k,'=',$v);
								}
							}

							// save the filter data for the url
							if(isset($columns[$k])){
								$controller->params['named'][$key.'.'.$k] = $v;
							}
                        }
                    }

                    //unsetting the empty forms
                    //if(count($value) == 0){
                    //    unset($controller->data[$key]);
                    //}
				}
            }
        }

	return $ret;
    }

    /**
     * function which will take care of the storing the filter data and loading after this from the Session
	 * JF: modified to not htmlencode, caused problems with dates e.g. -05-
	 * @param object $controller the class of the controller which call this component
     */
    function _prepareFilter($controller) {
		if(isset($controller->data['SerializedParams']['named'])){
			//pr('unserialize');
			$controller->data = array_merge($controller->data,unserialize($controller->data['SerializedParams']['named']));
			$controller->params['named'] = unserialize($controller->data['SerializedParams']['named']);
			if(isset($controller->paginate) && isset($controller->params['named']['limit']))
				$controller->paginate['limit'] = $controller->params['named']['limit'];
		}
		//pr($controller->data);
		$filter = array();
        if(isset($controller->data)) {
			//pr($controller->data);
            foreach($controller->data as $model=>$fields) {
				if(is_array($fields)) {
					foreach($fields as $key=>$field) {
						if($field == '') {
							unset($controller->data[$model][$key]);
						}

						if(is_string($field) && 
							preg_match('/start_date (?P<startdate>\d{4}-\d{2}-\d{2}) end_date (?P<enddate>\d{4}-\d{2}-\d{2})/',$field,$match)){
						//if($key == 'complete_date' && is_string($field)){
							//pr($field);
							//$fieldArr = preg_split('/\ /',$field);
							//pr($fieldArr);
							//if($fieldArr[0] == 'start_date' && $fieldArr[2] == 'end_date'){
							//	$controller->data[$model][$key] = array($fieldArr[0]=>$fieldArr[1],$fieldArr[2]=>$fieldArr[3]);
							//} 
							if(isset($match['startdate']) && isset($match['enddate'])){
								$field = array('start_date'=>$match['startdate'],'end_date'=>$match['enddate']);
								$controller->data[$key][$k] = $field;
								//pr($controller->data);	
							}
						}
					}
				}
            }

            App::import('Sanitize');
            $sanit = new Sanitize();
            $controller->data = $sanit->clean($controller->data, array('encode' => false));
            $filter = $controller->data;
        }

        if (empty($filter)) {
      		$filter = $this->_checkParams($controller);
        }
		//if (!empty($filter)) $controller->data['filter'] = 'Filter';
        $controller->data = $filter;
		
	return $controller;
    }


    /**
     * function which will take care of filters from URL
	 * JF: modified to not encode, caused problems with dates
	 * @param object $controller the class of the controller which call this component
     */
     function _checkParams($controller) {
		
     	if (empty($controller->params['named'])) {
     		$filter = array();
     	}

        App::import('Sanitize');
        $sanit = new Sanitize();
        $controller->params['named'] = $sanit->clean($controller->params['named'],array('encode' => false));
		//pr($controller->params['named']);
     	foreach($controller->params['named'] as $field => $value) {
     		if(!in_array($field, $this->paginatorParams)) {
				$fields = explode('.',$field);
				if (sizeof($fields) == 1) {
	     			$filter[$controller->modelClass][$field] = $value;
				} else {
	     			$filter[$fields[0]][$fields[1]] = $value;
				}
     		}
     	}
     	if (!empty($filter))
     		return $filter;
     	else
     		return array();
     }


	/**
	 * Prepares a date array for a Mysql where clause
	 * @author James Fairhurst
	 * @param array $arr
	 * @return string
	 */
	function _prepare_datetime($date) {
		$str = '';
		if(is_array($date)){
			//pr($date);
			// reverse array so that dd-mm-yyyy becomes yyyy-mm-dd
			$date = array_reverse($date);
			// loop through date
			$useCakeDates = 0;
			foreach($date as $key=>$value) {
				// if d/m/y has been entered
				if(!empty($value)) {
					if($key=='year' || $key=='month' || $key=='day'){
						$arr[] = $value;
						$useCakeDates = 1;
					} 
					if($key == 'start_date' || $key == 'end_date'){
						$date[$key] = date('Y-m-d',strtotime($date[$key]));
						$str = $date;
					}
				}
			}
			if($useCakeDates) $str = implode('-',$arr);
		} else {

			$str = date('Y-m-d',strtotime($date));

		}
		//pr($date);
	return $str;
	}
}

?>
