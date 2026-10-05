(function () {
	document.querySelectorAll(".spwa-wish").forEach(function (btn) {
		btn.addEventListener("click", function (event) {
			event.preventDefault();
			if (typeof spwaFront === "undefined") {
				return;
			}
			var body = new URLSearchParams();
			body.append("action", "spwa_wishlist_toggle");
			body.append("nonce", spwaFront.nonce);
			body.append("product_id", btn.getAttribute("data-id"));
			fetch(spwaFront.ajaxUrl, {
				method: "POST",
				credentials: "same-origin",
				headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
				body: body.toString()
			}).then(function (res) {
				return res.json();
			}).then(function (json) {
				if (!json || !json.success) {
					return;
				}
				var on = !!json.data.in;
				btn.classList.toggle("is-in", on);
				btn.textContent = on ? btn.getAttribute("data-in") : btn.getAttribute("data-add");
			});
		});
	});

	var bar = document.querySelector(".spwa-sticky-atc");
	var form = document.querySelector("form.cart");
	if (bar && form) {
		var trigger = form.querySelector(".single_add_to_cart_button");
		var show = function (visible) {
			if (visible) {
				bar.removeAttribute("hidden");
			} else {
				bar.setAttribute("hidden", "hidden");
			}
		};
		if ("IntersectionObserver" in window && trigger) {
			var observer = new IntersectionObserver(function (entries) {
				entries.forEach(function (entry) {
					show(!entry.isIntersecting);
				});
			});
			observer.observe(trigger);
		} else {
			show(true);
		}
		var stickyBtn = bar.querySelector(".spwa-sticky-atc__btn");
		if (stickyBtn && trigger) {
			stickyBtn.addEventListener("click", function () {
				trigger.click();
			});
		}
	}
})();
