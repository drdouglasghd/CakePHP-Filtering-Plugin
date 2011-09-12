<?php 
	if(isset($filter)){
		echo $this->Html->link('Filter Link',$this->Paginator->options['url'],array('class'=>'filter-link')); 
		//pr($filter);
		$c[] = 'Showing:';
		foreach($filter['FilterExpl'] as $filterCond){
			if(count($filter['FilterExpl']) > 1 && count($c) != count($filter['FilterExpl'])){ $oper = 'and'; }else{ $oper = '';}
			$c[] = $this->Html->tag('li',$filterCond . ' ' . $oper);
		} 
		if(count($c) > 1) echo $this->Html->tag('ul',implode($c,''),array('class'=>'filter_list'));
	}
	$currentUrl = $this->Paginator->options['url'];
	//pr($this->params);
	if(isset($this->params['paging'])){
			foreach($this->params['paging'] as $model => $pagingItem){
				if($pagingItem['count'] > $pagingItem['options']['limit']){
					$limitOptions = array(
						(round($pagingItem['options']['limit'] / 4)) => (round($pagingItem['options']['limit'] / 4)),
						(round($pagingItem['options']['limit'] / 2)) => (round($pagingItem['options']['limit'] / 2)),
						$pagingItem['options']['limit'] => $pagingItem['options']['limit'],
						($pagingItem['options']['limit'] * 2) => ($pagingItem['options']['limit'] * 2),
						($pagingItem['options']['limit'] * 4) => ($pagingItem['options']['limit'] * 4),
						($pagingItem['options']['limit'] * 6) => ($pagingItem['options']['limit'] * 6),
						($pagingItem['options']['limit'] * 8) => ($pagingItem['options']['limit'] * 8),
						($pagingItem['options']['limit'] * 10) => ($pagingItem['options']['limit'] * 10),
					);
					echo $this->Form->input('limit',array(
						'label'=>'Page Size','id'=>'LimitSetter',
						'default'=>$pagingItem['options']['limit'],
						'options'=>$limitOptions)
					);
				}
			}
	}
	$namedParamStr = '';
	foreach($this->params['named'] as $key => $val){
		$namedParamStr .=  sprintf('/%s:%s',$key,$val);
	}
?>
<script>
var baseUrl = '<?php printf('/%s/%s%s/',$this->params['controller'],$this->params['action'],$namedParamStr); ?>';

$('#LimitSetter').change(function(event){
	if(baseUrl.match(/(limit:).[0-9](\/|)/i)){
		window.location = baseUrl.replace(/(limit:).[0-9](\/|)/i,'limit:' + $(this).val() + '/');
	} else {
		window.location = baseUrl + 'limit:' + $(this).val();
	}
});
</script>