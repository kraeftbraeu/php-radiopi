var rest = require("./rest.js");
//var data = require("./data.js");
var domUtil = require("./domUtil.js");

customElements.define('mk-radiopi',
	class RadioPi extends HTMLElement {

		waiterDiv;
		statusDiv;
		volumeInput;

		connectedCallback() {
			let template = document.getElementById('tRadioPi').content.cloneNode(true);
			this.appendChild(template);
			this.waiterDiv = this.getElementById("waiter");
			this.statusDiv = this.getElementById("status");
			this.volumeInput = this.getElementById("volume");
		}

		addWait(waitItem) {
			this.waiterDiv.classList.add(waitItem); // was wenn sich zwei identische waitItems überschneiden?
			this.waiterDiv.style.display = "";
		}
		
		removeWait(waitItem) {
			this.waiterDiv.classList.remove(waitItem);
			if(this.waiterDiv.classList.length == 0) {
				this.waiterDiv.style.display = "none";
			}
		}

		loadStatus()
		{
			rest.loadStatus().then(
				data => this.printStatus(data)
			).catch(error => {
				this.printStatus("error loading status: " + error);
				console.error(error);
			});
		}
		
		printStatus(data) {
			if(data.volume !== undefined)
				this.volumeInput.value = data.volume;
			if(data.playing !== undefined && !data.playing.startsWith("volume:"))
				statusDiv.innerText = "playing: " + data.playing;
			else
				statusDiv.innerText = "stopped";
		}
	}
);

customElements.define('mk-button',
	class RadioPiButton extends HTMLElement {

		connectedCallback() {
			let template = domUtil.stringToHtml(`<script defer src="../js/fontawesome-all.min.js"></script>
				<a class="navbar-brand">
					<i class="fas fa-3x"></i>
				</a>`);

			let icon = this.getAttribute("icon");
			if(icon) {
				this.textContent = "";
				let i = template.querySelectorAll("i")[0];
				i.classList.add(icon);
			}
			this.addEventListener("click", e => {
				console.log(this.getAttribute("do")); // TODO: string to executable function or subclasses
			});
			
			this.appendChild(template);
		}
	}
);

customElements.define('mk-playlist',
	class RadioPiPlaylist extends HTMLElement {

		senderList;
		radioPi;

		connectedCallback() {
			let template = document.getElementById('tPlaylist').content.cloneNode(true);
			this.appendChild(template);
			this.senderList = this.querySelector("tbody");
			this.radioPi = document.querySelector("mk-radiopi");
			loadSenders();
		}

		clearSenders() {
			while(this.senderList.firstChild) {
				this.senderList.removeChild(this.senderList.firstChild);
			}
		}

		loadSenders() {
			this.clearSenders();
			this.radioPi.addWait("loadSenders");
			rest.loadSenders().then(list => {
				for(let i = 0; i < list.length; i++) {
					this.addSender(list[i].name, list[i].url);
				}
				this.radioPi.removeWait("loadSenders");
			}).catch(error => {
				this.radioPi.printStatus("error loading senders: " + error);
				console.error(error);
				this.radioPi.removeWait("loadSenders");
			});
		}

		addSender(senderName, senderUrl) { // TODO: eigenes custom element
			let senderTemplate = document.getElementById("tSenderRow").content.cloneNode(true);
			let id = senderName + "-" + senderUrl + "-" + Math.random();
			let index = senderList.childElementCount + 1;
			senderTemplate.querySelectorAll("mk-button")[0].href = "javascript:play('" + index + "');";
			senderTemplate.querySelectorAll("mk-button")[1].href = "javascript:removeSender('" + id + "');";
			senderTemplate.querySelectorAll("mk-button")[2].href = "javascript:moveSender('" + id + "');";
			let tr = senderTemplate.querySelector("tr");
			tr.setAttribute("id", id);
			this.senderList.append(senderTemplate);
			return tr;
		}

		removeSender(senderId) {
			this.senderList.removeChild(this.senderList.getElementById(senderId));
		}

		saveSenderList() {
			// collect list
			let trs = this.querySelectorAll("tbody tr");
			let senders = [];
			for(let i = 0; i < trs.length; i++) {
				let inputs = trs[i].getElementsByTagName("input");
				let senderName = inputs[0].value;
				let senderUrl = inputs[1].value;
				if(senderName != "" && senderUrl != "") {
					let sender = {"name": senderName, "url": senderUrl};
					senders.push(sender);
				}
			}
			// send list
			this.radioPi.addWait("saveSenders");
			rest.saveSenders(senders).then(list => {
				this.clearSenders();
				for(let i = 0; i < list.length; i++) {
					this.addSender(list[i].name, list[i].url);
				}
				this.radioPi.removeWait("saveSenders");
			}).catch(error => {
				this.radioPi.printStatus("error saving status: " + error);
				console.error(error);
				this.radioPi.removeWait("saveSenders");
			});
			this.toggleFromEdit();
		}

		resetSenderList() {
			this.loadSenders();
			this.toggleFromEdit();
		}
	}
);

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
	}, "play");
}

function stop()
{
	doPost({
		'do': 'stop'
	}, "stop");
}

function plus()
{
	doPost({
		'do': 'plus'
	}, "plus");
}

function minus()
{
	doPost({
		'do': 'minus'
	}, "minus");
}

function volume(volume)
{
	doPost({
		'do': 'volume',
		'volume': volume
	}, "volume");
}

function shutdown()
{
	doPost({
		'do': 'shutdown'
	}, "shutdown");
}

function doPost(paramMap, caller)
{
	addWait(caller);
	rest.doPost(paramMap, caller).then(data => {
		printStatus(data);
		removeWait(caller);
	}).catch(error => {
		document.getElementById("status").innerText = "error loading status: " + error;
		removeWait(caller);
	});
}

function changeVolume(is) {
	let volumeBar = document.getElementById("volume");
	this.volume(volumeBar.value);

	// may be called by onchange() and oninput()
	var other = is=="change" 
		? "input" 
		: "change";
	volumeBar.removeAttribute("on" + other);
	// prevent double request when double click
	setTimeout(function() {
		volumeBar.setAttribute("on" + other, "changeVolume('on" + other + "')");
	}, 1000);
}

function toggleButton(thisButton, isActive) {
	if(isActive)
		thisButton.classList.remove("active");
	else
		thisButton.classList.add("active");
	let buttons = document.getElementsByTagName("nav")[0].getElementsByTagName("a");
	for(let i = 0; i < buttons.length; i++) {
		if(!isActive && buttons[i] !== thisButton) {
			buttons[i].removeAttribute("href");
			buttons[i].style.color = "#4af";
		} else {
			buttons[i].setAttribute("href", "javascript:" + buttons[i].getAttribute("id") + "();");
			buttons[i].style.color = "";
		}
	}
	let volumeBar = document.getElementById("volumebar");
	let volumeButtons = volumeBar.querySelectorAll("a");
	let newColor = isActive ? "" : "#4af";
	for(let i = 0; i < volumeButtons.length; i++) {
		volumeButtons[i].style.color = newColor;
	}
	let volumeInput = volumeBar.querySelector("input");
	volumeInput.style.background = newColor;
	if(isActive)
		volumeInput.removeAttribute("disabled");
	else
		volumeInput.setAttribute("disabled", "true");
}

function toggleSettings() {
	let thisButton = document.getElementById("toggleSettings");
	let isActive = thisButton.classList.contains("active");
	this.toggleButton(thisButton, isActive);
	
	let playlist = document.getElementById("playlist");
	let settingslist = document.getElementById("settingslist");
	if(isActive) {
		playlist.style.display = "";
		settingslist.style.display = "none";
	} else {
		playlist.style.display = "none";
		settingslist.style.display = "";
	}
}

function toggleToEdit() {

	// deactivate all nav buttons
	let thisButton = document.getElementById("toggleToEdit");
	this.toggleButton(thisButton, false);
	thisButton.removeAttribute("href");
	thisButton.style.color = "#4af";

	// show save/reset buttons
	document.querySelector("#playlist thead").style.display = "";
	
	// switch text to input
	let rows = document.querySelectorAll("#playlist tbody tr");
	for(let i = 0; i < rows.length; i++) {
		this.switchRowToEdit(rows[i]);
	}
	
	// show add button
	document.querySelector("#playlist tfoot").style.display = "";

	// hide url column when too small
	document.getElementById("playlist").classList.remove("viewmode");
}

function toggleFromEdit() {

	// show all nav buttons
	let thisButton = document.getElementById("toggleToEdit");
	this.toggleButton(thisButton, true);
	thisButton.setAttribute("href", "javascript:" + thisButton.getAttribute("id") + "();");
	thisButton.style.color = "";

	// hide save/reset buttons
	document.querySelector("#playlist thead").style.display = "none";

	// switch input to text
	let rows = document.querySelectorAll("#playlist tbody tr");
	for(let i = 0; i < rows.length; i++) {
		this.switchRowToView(rows[i]);
	}

	// hide add button
	document.querySelector("#playlist tfoot").style.display = "none";

	// hide url column when too small
	document.getElementById("playlist").classList.add("viewmode");
}

function switchRowToEdit(tr) {
	let tds = tr.getElementsByTagName("td");
	let input = document.createElement("input");
	input.setAttribute("type", "text");
	input.setAttribute("name", "senderName");
	input.setAttribute("value", tds[1].textContent);
	tds[1].textContent = "";
	tds[1].appendChild(input);

	input = document.createElement("input");
	input.setAttribute("type", "text");
	input.setAttribute("name", "senderUrl");
	input.setAttribute("value", tds[2].textContent);
	tds[2].textContent = "";
	tds[2].appendChild(input);

	tds[0].style.display = "none";
	tds[3].style.display = "";
	
	tr.setAttribute("draggable", true);
	//td[3].getElementsByTagName("a")[1].addEventListener("dragstart", handleDragStart, false);
	// TODO
}

function switchRowToView(tr) {
	let tds = tr.getElementsByTagName("td");
	tds[1].textContent = tds[1].getElementsByTagName("input")[0].value;
	tds[2].textContent = tds[2].getElementsByTagName("input")[0].value;
	tds[0].style.display = "";
	tds[3].style.display = "none";
	
	tr.removeAttribute("draggable");
}

function handleDragStart(event) {
	this.style.opacity = "0.4";
}

function clearSenders(senderList) {
	while(senderList.firstChild) {
		senderList.removeChild(senderList.firstChild);
	}
}

function removeSender(trId) {
	document.querySelector("#playlist tbody").removeChild(document.getElementById(trId));
}