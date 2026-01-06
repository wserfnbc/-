
(function () {
	$(document).find('.image-loading').each(function () {
		const $img = $(this);
		const $container = $img.parent('.image-container');
		const src = $img.data('src');

		// 预加载图片
		const img = new Image();
		img.src = src;

		img.onload = function () {
			$img.attr('src', src);
			$img.addClass('image-loaded');
			// 关键修改：给容器添加loaded类以隐藏伪元素
			$container.addClass('loaded');
		};
	});
	$(document).on('mousemove', '.capsule', function (e) {
		// 获取元素的边界矩形，包含更准确的位置和尺寸信息
		const rect = this.getBoundingClientRect();

		// 计算鼠标在元素内的相对坐标
		const x = e.clientX - rect.left;
		const y = e.clientY - rect.top;

		// 限制x和y在元素范围内（0到宽/高之间）
		const clampedX = Math.max(0, Math.min(x, rect.width));
		const clampedY = Math.max(0, Math.min(y, rect.height));

		// 计算百分比（保留两位小数）
		const leftPercent = ((clampedX / rect.width) * 100).toFixed(2);
		const topPercent = ((clampedY / rect.height) * 100).toFixed(2);

		$(this).find('.fill-circle').css({
			'--x': `${leftPercent}%`,
			'--y': `${topPercent}%`,
		});
	});
	new WOW({
		boxClass: 'wow',
		animateClass: 'animate__animated',
		offset: 100,
		mobile: true,
		live: true
	}).init();




	// 回到顶部功能
	$(document).on("click", "[back-btn]", function (event) {
		event.preventDefault();
		$("html, body").animate(
			{
				scrollTop: 0,
			},
			600
		);
		return false;
	});

	let lastScrollTop1 = 0;
	// 处理header滚动效果
	window.addEventListener('scroll', function () {
		const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
		if (scrollTop > lastScrollTop1) {
			$("header").addClass("down");
		} else {
			$("header").removeClass("down");
		}
		lastScrollTop1 = scrollTop;

		var scroH = $(document).scrollTop();
		if (scroH > 1) {
			$("header").addClass("bgcolor");
		} else {
			$("header").removeClass("bgcolor");
		}
	});
})()
