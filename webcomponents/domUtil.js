exports.stringToHtml = function(string)
{
	var wrapper = document.createElement("div");
	wrapper.innerHTML = string;
	return wrapper.firstChild;
}