
window.addEventListener("load", () => {
  const loader = document.querySelector(".loader");
  //SetTimeout should be removed before Going live.
  setTimeout(() => {
    loader.classList.add("loader-hidden");
    loader.addEventListener("transitionend", () => {
      document.body.removeChild("loader");
    });
  },1000);

});
