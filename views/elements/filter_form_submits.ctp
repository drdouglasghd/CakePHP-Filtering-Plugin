<?php echo $this->Form->hidden('SerializedParams.named',array('value'=>serialize($this->params['named']))); ?>
<div class="submit side-by-side filter-actions filter-on"><input type="submit" name="data[filter]" value="Search"></div>
<div class="submit side-by-side filter-actions"><input type="submit" name="data[reset]" value="Reset"></div> 