/*
  Requirement: Populate the "Weekly Course Breakdown" list page.

  Instructions:
  1. Link this file to `list.html` using:
     <script src="list.js" defer></script>

  2. In `list.html`, add an `id="week-list-section"` to the
     <section> element that will contain the weekly articles.

  3. Implement the TODOs below.
*/

// --- Element Selections ---
const listSection = document.querySelector("#week-list-section"); 

// TODO: Select the section for the week list ('#week-list-section').

// --- Functions ---

/**
 * TODO: Implement the createWeekArticle function.
 * It takes one week object {id, title, startDate, description}.
 * It should return an <article> element matching the structure in `list.html`.
 * - The "View Details & Discussion" link's `href` MUST be set to `details.html?id=${id}`.
 * (This is how the detail page will know which week to load).
 */
function createWeekArticle(week) {
  // ... your implementation here ...
  const article = document.createElement("article");

  const heading = document.createElement("h2");
  heading.textContent = week.title || "Untitled Week";

  const startDatePara = document.createElement("p");
  startDatePara.textContent = `Starts on: ${week.startDate || "TBD"}`;

  const descriptionPara = document.createElement("p");
  descriptionPara.textContent = week.description || "Description coming soon.";

  const link = document.createElement("a");
  const weekId = week.id || "";
  link.href = weekId ? `details.html?id=${weekId}` : "details.html";
  link.textContent = "View Details & Discussion";

  article.appendChild(heading);
  article.appendChild(startDatePara);
  article.appendChild(descriptionPara);
  article.appendChild(link);

  return article;
}

/**
 * TODO: Implement the loadWeeks function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'weeks.json'.
 * 2. Parse the JSON response into an array.
 * 3. Clear any existing content from `listSection`.
 * 4. Loop through the weeks array. For each week:
 * - Call `createWeekArticle()`.
 * - Append the returned <article> element to `listSection`.
 */
async function loadWeeks() {
  // ... your implementation here ...
  try {
    if (!listSection) {
      throw new Error("Week list section is missing from the DOM.");
    }

    const response = await fetch("weeks.json");
    if (!response.ok) {
      throw new Error(`Failed to load weeks.json: ${response.status}`);
    }

    const data = await response.json();
    const weeks = Array.isArray(data) ? data : [];

    listSection.innerHTML = "";

    if (weeks.length === 0) {
      const emptyParagraph = document.createElement("p");
      emptyParagraph.textContent = "No weekly content is available yet.";
      listSection.appendChild(emptyParagraph);
      return;
    }

    weeks.forEach(week => {
      const article = createWeekArticle(week);
      listSection.appendChild(article);
    });

  } catch (error) {
    console.error(error);
    if (listSection) {
      listSection.textContent = "Error loading weeks.";
    }
  }
}

// --- Initial Page Load ---
// Call the function to populate the page.
loadWeeks();
