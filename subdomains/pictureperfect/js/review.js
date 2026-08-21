//script for mobile nav
let toogleClick = document.querySelector(".toogleBox");
let container = document.querySelector(".container");
toogleClick.addEventListener("click", () => {
  if (toogleClick.classList.contains("active")) {
    toogleClick.classList.remove("active");
    container.classList.remove("active");
  } else {
    toogleClick.classList.add("active");
    container.classList.add("active");
  }
});
let reviewModalBtn = document.querySelector(".addReviewBtn");
let modal = document.querySelector(".modal");
let overlay = document.querySelector(".overlay");
reviewModalBtn.addEventListener("click", () => {
  modal.classList.add("display");
  overlay.style.display = "block";
});
overlay.addEventListener("click", () => {
  modal.classList.remove("display");
  overlay.style.display = "none";
});
let cancelBtn = document.querySelector(".cancel");
cancelBtn.addEventListener("click", (e) => {
  e.preventDefault();
  modal.classList.remove("display");
  overlay.style.display = "none";
});

let sendData = document.querySelector("#sendData");
let allReviews = document.querySelector(".all-reviews");
let totalRating = document.querySelector(".total-rating");
const showData = () => {
  (async () => {
    allReviews.innerHTML = "<h2>All Reviews</h2>";
    const rawResponse = await fetch("showData.php");
    const content = await rawResponse.json();
    let allReviewsNum = content.data[0].total;
    totalRating.innerHTML = `${allReviewsNum} Review`;
    let totalPoints = 0;
    if (content.success == true) {
      allReviews.innerHTML = "";
      allReviews.innerHTML = "<h2>All Reviews</h2>";
      const pointCounts = {};
      content.data.forEach((review) => {
        const pointValue = review.points;
        if (pointValue in pointCounts) {
          pointCounts[pointValue]++;
        } else {
          pointCounts[pointValue] = 1;
        }
      });
      content.data.forEach((review) => {
        totalPoints += +review.points;
        const reviewDiv = document.createElement("div");
        reviewDiv.classList.add("review");

        const reviewerNameDiv = document.createElement("div");
        reviewerNameDiv.classList.add("reviewer-name");
        reviewerNameDiv.textContent = review.rname;
        reviewDiv.appendChild(reviewerNameDiv);

        const reviewerEmailDiv = document.createElement("div");
        reviewerEmailDiv.classList.add("reviewer-email");
        reviewerEmailDiv.textContent = review.remail;
        reviewDiv.appendChild(reviewerEmailDiv);

        const starDiv = document.createElement("div");
        starDiv.classList.add("star");
        starDiv.textContent = `Rating: ${review.points} / 5`;
        reviewDiv.appendChild(starDiv);

        const reviewerLevel = document.createElement("div");
        reviewerLevel.classList.add("reviewer-level");
        reviewerLevel.textContent =
          review.role == 0 ? "Unverified User" : "Verified User";
        reviewDiv.appendChild(reviewerLevel);

        const reviewerCommentP = document.createElement("p");
        reviewerCommentP.classList.add("reviewer-comment");
        reviewerCommentP.innerHTML = `Title: ${review.rtitle}<br>Summary: ${review.rsummary}`;
        reviewDiv.appendChild(reviewerCommentP);
        allReviews.appendChild(reviewDiv);
      });

      for (let i = 1; i <= 5; i++) {
        const count = pointCounts[i] || 0;
        const percentage = (count / allReviewsNum) * 100;
        const ratingBar = document.querySelector(`#rating-${i}`);
        ratingBar.style.width = `${percentage}%`;
      }
      const avgpoints = document.querySelector("#avg-points");
      avgpoints.innerHTML = (totalPoints / allReviewsNum).toFixed(2);
    }
  })();
};
showData();

sendData.addEventListener("click", (e) => {
  e.preventDefault();
  let reviewTitle = document.querySelector("#review-title");
  let reviewSummary = document.querySelector("#review-summary");
  if (reviewTitle.value !== "" && reviewSummary.value !== "") {
    let points = document.querySelector("#rating");
    let data = new FormData();
    data.append("points", points.value);
    data.append("reviewTitle", reviewTitle.value);
    data.append("reviewSummary", reviewSummary.value);

    (async () => {
      const rawResponse = await fetch("reviewBackEnd.php", {
        method: "POST",
        body: data,
      });
      const content = await rawResponse.json();

      if (content.success == true) {
        showData();
        modal.classList.remove("display");
        overlay.style.display = "none";
      } else {
        console.log(content);
      }
      reviewTitle.value = "";
      reviewSummary.value = "";
    })();
  } else {
    const modalError = document.querySelector(".modal-error");
    modalError.textContent = "fill all fields";
  }
});
