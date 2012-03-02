<?php
?>
<form class="form-search"
	action="<?php echo BASE_URL . 'person/search/' ?>">
	<input autocomplete="off" class="input-medium search-query" type="text"
		maxlength="256" name="q" label="Find People" placeholder="Find People">
	<button type="submit" class="btn">Search</button>
</form>