$(document).ready(function () {
  // Preloader (يختفي بعد تحميل الصفحة)
  $(".preloader, .spinerLoader")
    .delay(1000)
    .fadeOut(500, function () {
      // Dynamic AOS delay
      $("section").each(function () {
        $(this)
          .find("[data-aos]")
          .each(function (index) {
            if (!$(this).attr("data-aos-delay")) {
              $(this).attr("data-aos-delay", (index + 1) * 100);
            }
          });
      });

      // AOS init
      AOS.init({
        offset: 20,
        delay: 50,
        duration: 750,
        once: true,
      });
    });

  // Main Slider
  var mainSlider = new Swiper(".mainSlider .swiper", {
    spaceBetween: 0,
    loop: true,
    effect: "fade",
    speed: 500,
    autoplay: {
      delay: 5000,
      disableOnInteraction: false,
    },
    navigation: {
      nextEl: ".mainSlider .swiper-button-next",
      prevEl: ".mainSlider .swiper-button-prev",
    },
  });

  // References Slider
  var swiper = new Swiper(".referencesSlider", {
    navigation: {
      nextEl: ".swiper-button-next",
      prevEl: ".swiper-button-prev",
    },
    centeredSlides: true,
    loop: true,
    slidesPerView: "auto",
    spaceBetween: 5,
    speed: 1000,
    autoplay: {
      delay: 1500,
      disableOnInteraction: false,
    },
  });

  // Counter Animation
  const counters = document.querySelectorAll(".counter");
  const speed = 200;
  const animateCount = (counter) => {
    const target = +counter.getAttribute("data-target");
    let count = 0;
    const step = Math.max(1, Math.ceil(target / speed));
    const update = () => {
      count += step;
      if (count < target) {
        counter.innerText = count.toLocaleString();
        requestAnimationFrame(update);
      } else {
        counter.innerText = target.toLocaleString();
      }
    };
    update();
  };
  const observer = new IntersectionObserver(
    (entries, obs) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          animateCount(entry.target);
          obs.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.1 }
  );
  counters.forEach((counter) => {
    observer.observe(counter);
  });

  // Phone Input Validation
  const phoneInput = document.getElementById("phoneNum");
  if (phoneInput) {
    phoneInput.addEventListener("input", function () {
      this.value = this.value.replace(/[^0-9]/g, "");
    });
  }

  // Bootstrap Form Validation
  (function () {
    "use strict";
    var forms = document.querySelectorAll(".needs-validation");
    Array.prototype.slice.call(forms).forEach(function (form) {
      form.addEventListener(
        "submit",
        function (event) {
          if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
          }
          form.classList.add("was-validated");
        },
        false
      );
    });
  })();

  ////
  // theme costomization
  $(document).ready(function () {
    // Retrieve values ​​from local storage if available
    var mainColor = localStorage.getItem("mainColor");
    var mainColor80 = localStorage.getItem("mainColor80");
    var mainColor10 = localStorage.getItem("mainColor10");

    // Apply saved values
    if (mainColor) {
      $(":root").css("--mainColor", mainColor);
      $("#colorInput").val(mainColor);
    }
    if (mainColor80) $(":root").css("--mainColor80", mainColor80);
    if (mainColor10) $(":root").css("--mainColor10", mainColor10);

    // Change theme on input
    $("#colorInput").on("input", function () {
      var newColor = $(this).val();

      $(":root").css("--mainColor", newColor);
      $(":root").css("--mainColor80", newColor + "33"); // 20%
      $(":root").css("--mainColor10", newColor + "1a"); // 10%

      // Save to localStorage
      localStorage.setItem("mainColor", newColor);
      localStorage.setItem("mainColor80", newColor + "33");
      localStorage.setItem("mainColor10", newColor + "1a");
    });
  });
});

// Back Button Function
function goBack() {
  window.history.back();
}

document.addEventListener("DOMContentLoaded", function () {
  new Swiper(".services-slider", {
    slidesPerView: 1,
    spaceBetween: 24,
    loop: true,
    navigation: {
      nextEl: ".services-slider .swiper-button-next",
      prevEl: ".services-slider .swiper-button-prev",
    },
    pagination: {
      el: ".services-slider .swiper-pagination",
      clickable: true,
    },
    breakpoints: {
      576: { slidesPerView: 2 },
      992: { slidesPerView: 3 },
      1200: { slidesPerView: 4 },
    },
  });
});
