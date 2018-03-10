function play()
{
	play(1);
}

function play(index)
{
	index = typeof index !== 'undefined' ? index : 1;
	doPost({
		'do': 'play',
		'index': index
	});
}

function stop()
{
	doPost({
		'do': 'stop'
	});
}

function plus()
{
	doPost({
		'do': 'plus'
	});
}

function minus()
{
	doPost({
		'do': 'minus'
	});
}

function volume(volume)
{
	doPost({
		'do': 'volume',
		'volume': volume
	});
}

function toggle()
{
	$(".addhide").toggle();
}

function removeEntry(index)
{
	doPost({
		'do': 'file',
		'index': index
	});
}

function addEntry()
{
	doPost({
		'do': 'file',
		'url': $('#addurl').val()
	});
}

function shutdown()
{
	doPost({
		'do': 'shutdown'
	});
}

function doPost(paramMap)
{
	var form = $('<form/>').attr('action','').hide();
	$.each(paramMap, function(key, val) {
		form.append(
			$('<input/>').attr('name', key).attr('value', val)
		);
	});
	form.attr('method', 'post')
		.appendTo('body')
		.submit();
}