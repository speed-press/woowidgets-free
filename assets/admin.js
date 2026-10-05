(function () {
	var app = document.querySelector(".spwa-app");
	if (!app || typeof spwaAdmin === "undefined") {
		return;
	}

	var crumb = document.getElementById("spwa-crumb-current");
	var search = document.getElementById("spwa-search");
	var current = "general";

	function panels() {
		return document.querySelectorAll("[data-spwa-panel]");
	}

	function showPanel(name) {
		current = name;
		document.querySelectorAll("[data-spwa-nav]").forEach(function (btn) {
			btn.classList.toggle("is-active", btn.getAttribute("data-spwa-nav") === name);
		});
		panels().forEach(function (panel) {
			var match = panel.getAttribute("data-spwa-panel") === name;
			panel.hidden = !match;
			panel.classList.toggle("is-filtered-out", !match);
		});
		var panel = document.querySelector('[data-spwa-panel="' + name + '"]');
		if (crumb && panel) {
			crumb.textContent = panel.getAttribute("data-label") || name;
		}
		if (name !== "search") {
			applyFilter();
		}
	}

	document.querySelectorAll("[data-spwa-nav]").forEach(function (btn) {
		btn.addEventListener("click", function () {
			if (search) {
				search.value = "";
			}
			showPanel(btn.getAttribute("data-spwa-nav"));
		});
	});

	document.querySelectorAll("[data-spwa-jump]").forEach(function (btn) {
		btn.addEventListener("click", function () {
			if (search) {
				search.value = "";
			}
			showPanel(btn.getAttribute("data-spwa-jump"));
		});
	});

	function post(data) {
		var body = new URLSearchParams();
		Object.keys(data).forEach(function (key) {
			var value = data[key];
			if (value && typeof value === "object") {
				Object.keys(value).forEach(function (sub) {
					body.append(key + "[" + sub + "]", value[sub]);
				});
			} else {
				body.append(key, value);
			}
		});
		body.append("nonce", spwaAdmin.nonce);
		return fetch(spwaAdmin.ajaxUrl, {
			method: "POST",
			credentials: "same-origin",
			headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
			body: body.toString()
		}).then(function (res) {
			return res.json();
		});
	}

	function applyFilter() {
		var panel = document.querySelector('[data-spwa-panel="' + current + '"]');
		if (!panel || current === "dashboard" || current === "search") {
			return;
		}
		var q = search ? search.value.toLowerCase().trim() : "";
		var shown = 0;
		panel.querySelectorAll(".spwa-card").forEach(function (card) {
			var hide = q && (card.getAttribute("data-name") || "").indexOf(q) === -1;
			card.hidden = hide;
			card.classList.toggle("is-filtered-out", hide);
			if (!hide) {
				shown++;
			}
		});
		var empty = panel.querySelector(".spwa-empty");
		if (empty) {
			empty.hidden = shown !== 0;
		}
	}

	function runSearch() {
		var q = search ? search.value.toLowerCase().trim() : "";
		if (!q) {
			showPanel(current === "search" ? "general" : current);
			return;
		}
		current = "search";
		document.querySelectorAll("[data-spwa-nav]").forEach(function (btn) {
			btn.classList.remove("is-active");
		});
		panels().forEach(function (panel) {
			var match = panel.getAttribute("data-spwa-panel") === "search";
			panel.hidden = !match;
			panel.classList.toggle("is-filtered-out", !match);
		});
		if (crumb) {
			crumb.textContent = "Search";
		}
		var list = document.getElementById("spwa-search-list");
		var count = document.getElementById("spwa-search-count");
		var empty = document.getElementById("spwa-search-empty");
		var query = document.getElementById("spwa-search-query");
		if (query) {
			query.textContent = "Showing addons matching “" + search.value.trim() + "”.";
		}
		if (list) {
			list.innerHTML = "";
		}
		var shown = 0;
		document.querySelectorAll(".spwa-panel:not([data-spwa-panel='search']) .spwa-card").forEach(function (card) {
			if ((card.getAttribute("data-name") || "").indexOf(q) === -1) {
				return;
			}
			shown++;
			if (list) {
				list.appendChild(card.cloneNode(true));
			}
		});
		if (count) {
			count.textContent = String(shown);
		}
		if (empty) {
			empty.hidden = shown !== 0;
		}
		bindCards(list);
	}

	if (search) {
		search.addEventListener("input", function () {
			if (search.value.trim()) {
				runSearch();
			} else {
				showPanel(current === "search" ? "general" : current);
			}
		});
		document.addEventListener("keydown", function (event) {
			if ((event.metaKey || event.ctrlKey) && (event.key || "").toLowerCase() === "k") {
				event.preventDefault();
				search.focus();
				search.select();
			}
		});
	}

	function syncEnabledList(card) {
		var list = document.getElementById("spwa-enabled-list");
		if (!list) {
			return;
		}
		var slug = card.getAttribute("data-slug");
		var existing = list.querySelector('[data-enabled-slug="' + slug + '"]');
		var on = card.querySelector(".spwa-toggle") && card.querySelector(".spwa-toggle").checked;
		if (on && !existing) {
			var li = document.createElement("li");
			li.setAttribute("data-enabled-slug", slug);
			var title = card.querySelector("h3");
			li.textContent = title ? title.textContent : slug;
			list.appendChild(li);
		}
		if (!on && existing) {
			existing.remove();
		}
		var none = list.querySelector(".spwa-none");
		if (none) {
			none.hidden = !!list.querySelector("[data-enabled-slug]");
		}
	}

	function bindCards(root) {
		var scope = root || document;
		scope.querySelectorAll(".spwa-toggle").forEach(function (input) {
			if (input.dataset.bound) {
				return;
			}
			input.dataset.bound = "1";
			input.addEventListener("change", function () {
				var card = input.closest(".spwa-card");
				var slug = card.getAttribute("data-slug");
				input.disabled = true;
				post({
					action: "spwa_toggle",
					slug: slug,
					enabled: input.checked ? "1" : "0"
				}).then(function (json) {
					input.disabled = false;
					if (!json || !json.success) {
						input.checked = !input.checked;
						return;
					}
					document.querySelectorAll('.spwa-card[data-slug="' + slug + '"]').forEach(function (node) {
						node.classList.toggle("is-on", input.checked);
						var box = node.querySelector(".spwa-toggle");
						if (box && box !== input) {
							box.checked = input.checked;
						}
						var state = node.querySelector(".spwa-state");
						if (state) {
							state.textContent = input.checked ? spwaAdmin.i18n.on : spwaAdmin.i18n.off;
						}
						var gear = node.querySelector(".spwa-gear");
						var form = node.querySelector(".spwa-settings");
						if (gear) {
							gear.hidden = !input.checked;
						}
						if (form && !input.checked) {
							form.hidden = true;
							if (typeof closeModal === "function") {
								closeModal();
							}
						}
					});
					var count = document.getElementById("spwa-on-count");
					if (count && json.data && typeof json.data.count !== "undefined") {
						count.textContent = json.data.count;
					}
					syncEnabledList(card);
				}).catch(function () {
					input.disabled = false;
					input.checked = !input.checked;
				});
			});
		});
		scope.querySelectorAll(".spwa-gear").forEach(function (btn) {
			if (btn.dataset.bound) {
				return;
			}
			btn.dataset.bound = "1";
			btn.addEventListener("click", function (event) {
				event.preventDefault();
				var card = btn.closest(".spwa-card");
				var toggle = card.querySelector(".spwa-toggle");
				if (!toggle || !toggle.checked) {
					return;
				}
				openModal(card);
			});
		});
		scope.querySelectorAll(".spwa-settings").forEach(function (form) {
			if (form.dataset.bound) {
				return;
			}
			form.dataset.bound = "1";
			form.addEventListener("submit", function (event) {
				event.preventDefault();
				var card = form.closest(".spwa-card");
				var note = form.querySelector(".spwa-save-note");
				var settings = {};
				form.querySelectorAll("input, textarea, select").forEach(function (field) {
					if (!field.name) {
						return;
					}
					settings[field.name] = field.type === "checkbox" ? (field.checked ? "1" : "0") : field.value;
				});
				post({
					action: "spwa_save_settings",
					slug: card.getAttribute("data-slug"),
					settings: settings
				}).then(function (json) {
					if (note) {
						note.textContent = json && json.success ? spwaAdmin.i18n.saved : spwaAdmin.i18n.error;
					}
				}).catch(function () {
					if (note) {
						note.textContent = spwaAdmin.i18n.error;
					}
				});
			});
		});
	}

	bindCards(document);

	var modal = document.getElementById("spwa-modal");
	var modalBody = document.getElementById("spwa-modal-body");
	var modalTitle = document.getElementById("spwa-modal-title");
	var parked = null;

	function closeModal() {
		if (!modal) {
			return;
		}
		if (parked && parked.form && parked.card) {
			parked.form.hidden = true;
			parked.card.appendChild(parked.form);
		}
		parked = null;
		modal.hidden = true;
	}

	function openModal(card) {
		var form = card.querySelector(".spwa-settings");
		if (!form || !modal || !modalBody) {
			return;
		}
		closeModal();
		var title = card.querySelector("h3");
		var desc = card.querySelector("p");
		var modalDesc = document.getElementById("spwa-modal-desc");
		if (modalTitle) {
			modalTitle.textContent = title ? title.textContent : "Settings";
		}
		if (modalDesc) {
			modalDesc.textContent = desc ? desc.textContent : "";
		}
		form.hidden = false;
		modalBody.appendChild(form);
		parked = { card: card, form: form };
		modal.hidden = false;
	}

	if (modal) {
		modal.addEventListener("click", function (event) {
			if (event.target.closest("[data-spwa-close]")) {
				closeModal();
			}
		});
		document.addEventListener("keydown", function (event) {
			if (event.key === "Escape") {
				closeModal();
			}
		});
	}

	document.querySelectorAll("[data-bulk]").forEach(function (btn) {
		btn.addEventListener("click", function () {
			var mode = btn.getAttribute("data-bulk");
			if (mode === "disable_all" && !window.confirm("Disable every addon?")) {
				return;
			}
			btn.disabled = true;
			post({ action: "spwa_bulk", mode: mode }).then(function () {
				window.location.reload();
			}).catch(function () {
				btn.disabled = false;
			});
		});
	});
})();
