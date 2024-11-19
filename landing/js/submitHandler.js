document.querySelectorAll(".my-form").forEach((form) => {
  form.addEventListener("submit", async (event) => {
    event.preventDefault(); // Запобігаємо перезавантаженню сторінки

    function setErrorMessageTo(fieldsList, message) {
      fieldsList.forEach((field) => (field.textContent = message));
    }

    let isValid = true; // Змінна для перевірки статусу валідності форми

    // Отримання даних з форми
    const formData = new FormData(form);

    // Валідація імені
    const firstName = formData.get("first_name");
    const firstNameErrorMessage = document.querySelectorAll(
      ".firstName-error-message"
    );

    if (!firstName) {
      setErrorMessageTo(firstNameErrorMessage, "The first name field is empty");
      isValid = false;
    } else if (firstName.length < 3) {
      setErrorMessageTo(
        firstNameErrorMessage,
        "First name must be at least 3 characters long"
      );
      isValid = false;
    } else {
      setErrorMessageTo(firstNameErrorMessage, "*");
    }

    // Валідація прізвища
    const lastName = formData.get("last_name");
    const lastNameErrorMessage = document.querySelectorAll(
      ".lastName-error-message"
    );

    if (!lastName) {
      setErrorMessageTo(lastNameErrorMessage, "The last name field is empty");
      isValid = false;
    } else if (lastName.length < 3) {
      setErrorMessageTo(
        lastNameErrorMessage,
        "Last name must be at least 3 characters long"
      );
      isValid = false;
    } else {
      setErrorMessageTo(lastNameErrorMessage, "*");
    }

    // Валідація телефону
    const phone = formData.get("phone");
    const phoneErrorMessage = document.querySelectorAll(".phone-error-message");

    if (!phone) {
      setErrorMessageTo(phoneErrorMessage, "The phone field is empty");
      isValid = false;
    } else if (!/^\+?[0-9\s\-\(\)]{11,}$/.test(phone)) {
      setErrorMessageTo(phoneErrorMessage, "Incorrect phone format");
      isValid = false;
    } else {
      setErrorMessageTo(phoneErrorMessage, "*");
    }

    // Валідація часу
    const appointment = formData.get("select_service");
    const appointmentErrorMessage = document.querySelectorAll(
      ".time-error-message"
    );

    if (appointment === "selecttime") {
      setErrorMessageTo(appointmentErrorMessage, "Choose a time");
      isValid = false;
    } else {
      setErrorMessageTo(appointmentErrorMessage, "*");
    }

    // Якщо форма не валідна, зупиняємо відправку
    if (!isValid) {
      return;
    }

    function formOpacity(opacity) {
      const forms = document.querySelectorAll(".contact_form");
      forms.forEach((form) => {
        form.style.opacity = opacity;
      });
    }

    function spinerToggle(state) {
      const spiner = document.querySelectorAll(".loader");
      spiner.forEach((e) => {
        e.style.display = state === "on" ? "inline-block" : "none";
      });
    }

    function createErrorMessage(container, message) {
      const errorMessage = document.createElement("div");
      errorMessage.classList.add("error-message");
      errorMessage.textContent = message;
      container.appendChild(errorMessage);
    }

    function createSkipErrorButton(container) {
      const skipErrorMessageButton = document.createElement("div");
      skipErrorMessageButton.textContent = "OK";
      skipErrorMessageButton.classList.add("skip-button");
      container.appendChild(skipErrorMessageButton);

      skipErrorMessageButton.addEventListener("click", function () {
        container.style.display = "none";
        formOpacity("100%");
      });
    }

    formOpacity("0.5");
    spinerToggle("on");

    const errorContainers = document.querySelectorAll(".error-wrapper");

    // Відправлення даних на сервер
    try {
      const response = await fetch("../../td-test-task/server/api.php", {
        method: "POST",
        body: formData,
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      const result = await response.json();

      if (result.status === "success") {
        const successEvent = new Event("success");
        form.dispatchEvent(successEvent);

        window.location.href = result.redirectUrl;
      } else {
        spinerToggle("off");

        errorContainers.forEach((container) => {
          container.textContent = "";
        });

        errorContainers.forEach((container) => {
          container.style.display = "flex";

          if (result.message) {
            createErrorMessage(container, result.message);
          }

          if (result.errors && result.errors.length > 0) {
            const errorHeader = document.createElement("div");
            errorHeader.textContent = "Fix the errors!";
            errorHeader.classList.add("error-header");
            container.appendChild(errorHeader);

            const errorList = document.createElement("div");
            errorList.classList.add("error-list");
            container.appendChild(errorList);

            result.errors.forEach((error) => {
              const errorItem = document.createElement("div");
              errorItem.classList.add("error-item");
              errorItem.textContent = error;
              errorList.appendChild(errorItem);
            });
          }

          createSkipErrorButton(container);
        });
      }
    } catch (error) {
      spinerToggle("off");
      errorContainers.forEach((container) => {
        container.textContent = "";
        container.style.display = "flex";

        createErrorMessage(container, "Error sending data. Try again later.");
        createSkipErrorButton(container);
      });
    } finally {
      formOpacity("1");
      spinerToggle("off");
    }
  });
});
