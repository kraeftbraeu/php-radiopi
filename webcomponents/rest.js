exports.doPost = function(paramMap)
{
	return fetch("../api.php", {
		method: "POST",
		dataType: "json",
		headers: {
			"Content-Type": "application/json;charset=UTF-8"
		},
		body: JSON.stringify(paramMap)
	}).then(
		res => res.json()
	);
}

exports.doGet = function(paramString)
{
	return fetch("../api.php?" + paramString, {
		method: "GET",
		dataType: "json",
		headers: {
			"Content-Type": "application/json;charset=UTF-8"
		}
	}).then(
		res => res.json()
	);
}

exports.loadStatus = function()
{
	return this.doGet("do=status");
}

exports.loadSenders = function()
{
	return this.doGet("do=load");
}

exports.saveSenders = function(senders)
{
	return this.doPost({
		"do": "save",
		"senders": senders
	});
}