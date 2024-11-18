//Фіксимо баг з навігаційним меню
const element = document.querySelector(".custom-nav");

// Спостерігаємо за змінами стилів елемента
const observer = new MutationObserver(() => {
  if (element.style.top !== "0px") {
    element.style.top = "0px"; // Встановлюємо top на 0px
  }
});

// Спостерігаємо тільки за атрибутами стилю
observer.observe(element, {
  attributes: true,
  attributeFilter: ["style"],
});


//Фіксимо баг з зображенням в header
const backgroundImageHeader = document.querySelector(".parallax");

if (backgroundImageHeader) {
  // Спостерігаємо за змінами стилів елемента
  const observer2 = new MutationObserver(() => {
    const computedStyle = window.getComputedStyle(backgroundImageHeader);

    // Перевіряємо значення backgroundPosition
    if (computedStyle.backgroundPosition !== "0px 0px") {
      backgroundImageHeader.style.backgroundPosition = "0px 0px"; // Встановлюємо позицію на 0px 0px
    }
  });

  // Спостерігаємо тільки за атрибутами стилю
  observer2.observe(backgroundImageHeader, {
    attributes: true,
    attributeFilter: ["style"],
  });
} else {
  console.error("Елемент .parallax не знайдено");
}
