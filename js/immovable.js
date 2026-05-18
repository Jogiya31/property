document.addEventListener("DOMContentLoaded", async function () {
  const params = new URLSearchParams(window.location.search);
  const editId = params.get("id");
  localStorage.removeItem("draftId");
  try {
    if (editId) {
      const res = await fetch("../api/getDataById.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ id: editId }),
      });

      const json = await res.json();

      if (json.success && json.data) {
        window.__editFormData = json.data;
      }
    }
  } catch (err) {
    console.error("Edit load error:", err);
  }

  initForm1();

  if (typeof hydrateForm1ForEdit === "function") {
    hydrateForm1ForEdit();
  }

  updatePropertyButtons();
  toggleAcquiredDisposed();
});

function initForm1() {
  const form = document.getElementById("form1");
  if (!form) return;

  const firstBlock = document.querySelector(".property-block");
  prefillApplicantRow(firstBlock);

  const acquiredDisposed = form.querySelector('[name="acquired_disposed"]');
  const purpose = form.querySelector('[name="purpose"]');

  const modeAcquisitionBlock = document.getElementById("mode_acquisition");
  const modeDisposalBlock = document.getElementById("mode_disposal");
  const acquisitionGift = document.getElementById("acquisition_gift");
  const date_acq_dis = document.getElementById("date_acq_dis");

  const form1Inparts = document.getElementById("form1_inparts");
  const form1Full = document.getElementById("form1_full");
  const dateLabel = document.getElementById("date_acquisition_disposed");

  /* =========================
     SHOW / HIDE
  ========================= */
  const safeShow = (el) => el && el.classList.remove("d-none");
  const safeHide = (el) => el && el.classList.add("d-none");

  function appendPropertyBlockForHydration() {
    const blocks = form.querySelectorAll(".property-block");
    const currentBlock = blocks[blocks.length - 1];
    if (!currentBlock) return null;

    const clone = currentBlock.cloneNode(true);

    clone.querySelectorAll("textarea").forEach((el, index) => {
      el.value = "";
      el.id = "editor_" + Date.now() + "_" + index;
    });

    clone.querySelectorAll("input").forEach((el) => {
      if (el.type !== "file") el.value = "";
      el.classList.remove("has-error");
    });

    clone.querySelectorAll('input[type="file"]').forEach((fileInput) => {
      const newInput = fileInput.cloneNode(true);
      newInput.value = "";
      fileInput.parentNode.replaceChild(newInput, fileInput);
    });

    clone.querySelectorAll("select").forEach((el) => {
      el.selectedIndex = 0;
    });

    clone
      .querySelectorAll(".row-item-applicant:not(:first-child)")
      .forEach((el) => el.remove());
    clone
      .querySelectorAll(".row-item-source:not(:first-child)")
      .forEach((el) => el.remove());
    clone.querySelectorAll(".price_in_text").forEach((el) => {
      el.textContent = "";
    });

    const cloneSource = clone.querySelector(".acquisition_sources");
    const cloneDisposal = clone.querySelector(".disposal_property");
    const currentSource = currentBlock.querySelector(".acquisition_sources");
    const currentDisposal = currentBlock.querySelector(".disposal_property");

    if (currentSource && currentSource.offsetParent !== null) {
      safeShow(cloneSource);
    } else {
      safeHide(cloneSource);
    }

    if (currentDisposal && currentDisposal.offsetParent !== null) {
      safeShow(cloneDisposal);
    } else {
      safeHide(cloneDisposal);
    }

    const relDesc = clone.querySelector(
      '[name="party_relationship_description[]"]',
    );
    if (relDesc) {
      relDesc.value = "";
      safeHide(relDesc);
    }

    const relSelect = clone.querySelector('[name="party_relationship[]"]');
    if (relSelect) relSelect.value = "";

    const dealingDesc = clone.querySelector(
      '[name="applicant_dealing_parties_description[]"]',
    );
    if (dealingDesc) {
      dealingDesc.value = "";
      safeHide(dealingDesc);
    }

    const dealingSelect = clone.querySelector(
      '[name="applicant_dealing_parties[]"]',
    );
    if (dealingSelect) dealingSelect.value = "";

    currentBlock.after(clone);
    updatePropertyButtons();
    toggleAcquiredDisposed();
    initTinyMCE();
    return clone;
  }

  window.__appendPropertyBlockForm1 = appendPropertyBlockForHydration;

  /* =========================
     REMOVE INVALID
  ========================= */
  function removeInvalid(field) {
    if (!field || !field.matches) return;

    // skip hidden fields
    if (field.disabled || field.hidden) return;

    let isValid = true;

    if (field.type === "checkbox") {
      isValid = field.checked;
    } else if (field.type === "file") {
      isValid = field.files.length > 0;
    } else {
      isValid = field.value && field.value.trim() !== "";
    }

    if (isValid) {
      field.classList.remove("has-error");

      // also clear custom error (if any)
      const block = field.closest(".property-block");
      if (block) {
        const interestError = block.querySelector(".interest_error");
        const sourceError = block.querySelector(".source_error");

        if (interestError) interestError.textContent = "";
        if (sourceError) sourceError.textContent = "";
      }
    }
  }

  form.addEventListener("input", (e) => removeInvalid(e.target));
  form.addEventListener("change", (e) => removeInvalid(e.target));

  /* =========================
     ACQUIRED / DISPOSED
  ========================= */
  function handleAcquiredDisposed() {
    const value = acquiredDisposed?.value;

    form.querySelectorAll(".property-block").forEach((block) => {
      const source = block.querySelector(".acquisition_sources");
      const disposal = block.querySelector(".disposal_property");
      const priceLabel = block.querySelector(".property_price_label");

      if (value === "acquired") {
        priceLabel &&
          (priceLabel.innerText = "Purchase price of the property (₹)");
        dateLabel && (dateLabel.innerText = "Probable date of acquisition");

        safeShow(modeAcquisitionBlock);
        safeHide(modeDisposalBlock);
        safeShow(source);
        safeHide(disposal);
        safeShow(date_acq_dis);
      } else if (value === "disposed") {
        priceLabel && (priceLabel.innerText = "Sale price of the property (₹)");
        dateLabel && (dateLabel.innerText = "Probable date of disposal");

        safeShow(modeDisposalBlock);
        safeHide(modeAcquisitionBlock);
        safeHide(source);
        safeShow(disposal);
        safeShow(date_acq_dis);
      } else if (value === "") {
        safeHide(source);
        safeHide(disposal);
        safeHide(modeDisposalBlock);
        safeHide(modeAcquisitionBlock);
        safeHide(date_acq_dis);
      }
    });

    toggleGift();
  }

  function toggleGift() {
    const mode = form.querySelector('[name="mode_acquisition"]')?.value;
    const giftFields = acquisitionGift?.querySelectorAll(
      "input,select,textarea",
    );

    if (mode === "Gift" && acquiredDisposed?.value === "acquired") {
      safeShow(acquisitionGift);
      giftFields?.forEach((f) => f.setAttribute("required", "required"));
    } else {
      safeHide(acquisitionGift);
      giftFields?.forEach((f) => {
        f.removeAttribute("required");
        f.classList.remove("has-error");
      });
    }
  }

  acquiredDisposed?.addEventListener("change", handleAcquiredDisposed);

  form.addEventListener("change", (e) => {
    if (e.target.name === "mode_acquisition") toggleGift();
  });

  /* =========================
     PURPOSE
  ========================= */
  purpose?.addEventListener("change", function () {
    if (this.value === "Sanction for transaction") {
      safeShow(form1Inparts);
      safeHide(form1Full);
    } else {
      safeShow(form1Full);
      safeHide(form1Inparts);
    }
  });

  /* =========================
     CLICK HANDLER
  ========================= */
  form.addEventListener("click", (e) => {
    /* ===== ADD APPLICANT ===== */
    if (e.target.closest(".addRow_applicant")) {
      const container = e.target.closest(".rows_applicant");
      const rows = container.querySelectorAll(".row-item-applicant");
      const lastRow = rows[rows.length - 1];
      const isHydratingEdit = window.__isHydratingEdit === true;

      if (!isHydratingEdit) {
        const nameInput = lastRow?.querySelector('[name="name_applicant[]"]');
        const interestInput = lastRow?.querySelector(
          '[name="interest_applicant[]"]',
        );
        const relSelect = lastRow?.querySelector(
          '[name="relationship_applicant[]"]',
        );

        if (shouldValidate(nameInput) && !nameInput.value.trim()) {
          return showError(
            nameInput,
            "Please complete the current applicant row (name) before adding another.",
          );
        }
        if (shouldValidate(interestInput)) {
          const iv = interestInput.value.trim();
          const n = Number(iv);
          if (!iv || Number.isNaN(n) || n < 0 || n > 100) {
            return showError(
              interestInput,
              "Please enter a valid interest % (0–100) for the current applicant before adding another.",
            );
          }
        }
        if (shouldValidate(relSelect) && !relSelect.value) {
          return showError(
            relSelect,
            "Please select relationship for the current applicant before adding another.",
          );
        }
      }

      const templateRow = container.querySelector(".row-item-applicant");
      const clone = templateRow.cloneNode(true);

      clone.querySelectorAll("input").forEach((i) => (i.value = ""));
      clone.querySelectorAll("select").forEach((s) => (s.selectedIndex = 0));

      container.appendChild(clone);
    }

    /* ===== REMOVE APPLICANT ===== */
    if (e.target.closest(".removeRow_applicant")) {
      const container = e.target.closest(".rows_applicant");
      if (container.children.length > 1) {
        e.target.closest(".row-item-applicant").remove();
      }
    }

    /* ===== ADD SOURCE ===== */
    if (e.target.closest(".addRow_source")) {
      const container = e.target.closest(".rows_source");
      const rows = container.querySelectorAll(".row-item-source");
      const lastRow = rows[rows.length - 1];
      const isHydratingEdit = window.__isHydratingEdit === true;

      if (!isHydratingEdit) {
        // Only validate required fields
        const requiredFields = lastRow.querySelectorAll("input[required]");

        let isValid = true;
        let firstInvalid = null;

        requiredFields.forEach((el) => {
          if (!el.value || el.value.trim() === "") {
            isValid = false;
            el.classList.add("error"); // optional styling
            if (!firstInvalid) firstInvalid = el;
          } else {
            el.classList.remove("error");
          }
        });

        if (!isValid) {
          showError(
            firstInvalid,
            "Please fill source name and amount fields before adding a new row.",
          );
          if (firstInvalid) firstInvalid.focus();
          return;
        }
      }

      // Clone last row
      const clone = lastRow.cloneNode(true);

      // Clear values
      clone.querySelectorAll("input").forEach((input) => {
        if (input.type === "file") {
          input.value = ""; // reset file input
          if (input.dataset) delete input.dataset.existingFileKey;
        } else {
          input.value = "";
        }
      });
      clone
        .querySelectorAll(
          ".js-existing-source-attachment, .js-existing-disposal-attachment",
        )
        .forEach((el) => {
          el.classList.add("d-none");
          el.innerHTML = "";
        });

      container.appendChild(clone);
    }

    /* ===== REMOVE SOURCE ===== */
    if (e.target.closest(".removeRow_source")) {
      const container = e.target.closest(".rows_source");
      if (container.children.length > 1) {
        e.target.closest(".row-item-source").remove();
      }
    }

    /* ===== ADD PROPERTY BLOCK ===== */
    if (e.target.closest(".addPropertyBtn")) {
      const isHydratingEdit = window.__isHydratingEdit === true;
      if (!isHydratingEdit && acquiredDisposed.value === "") {
        return showError(
          acquiredDisposed,
          "Please select Acquired or Disposed field first.",
        );
      }

      const currentBlock = e.target.closest(".property-block");

      if (!isHydratingEdit) {
        clearErrors();
      }

      if (!isHydratingEdit) {
        /* =========================
        REQUIRED FIELDS
      ========================= */
        let firstInvalid = null;

        currentBlock
          .querySelectorAll("input, select, textarea")
          .forEach((field) => {
            if (!shouldValidate(field)) return;
            if (field.type === "file") return;

            if (!field.value || field.value.trim() === "") {
              field.classList.add("has-error");
              if (!firstInvalid) firstInvalid = field;
            }
          });

        if (firstInvalid) {
          return showError(firstInvalid, "Please fill the required field");
        }

        /* =========================
        PRICE VALIDATION
      ========================= */

        const priceInput = currentBlock.querySelector(".property_price");
        if (!shouldValidate(priceInput)) return;
        const value = priceInput.value.trim();
        const price = Number(value);
        if (price > 999999999) {
          return showError(priceInput, "Amount exceeds 99.99 crores limit");
        }

        /* =========================
        INTEREST VALIDATION
      ========================= */
        let interestTotal = 0;
        const interestInputs =
          currentBlock.querySelectorAll(".interest_percent");

        interestInputs.forEach((i) => {
          if (i.value) interestTotal += Number(i.value) || 0;
        });

        if (interestTotal !== 100) {
          return showError(
            interestInputs[0],
            `Total interest must be 100% (current: ${interestTotal}%)`,
          );
        }

        /* =========================
        SOURCE VALIDATION
      ========================= */
        const sourceBlock = currentBlock.querySelector(".acquisition_sources");

        if (sourceBlock && isVisible(sourceBlock)) {
          const price =
            Number(currentBlock.querySelector(".property_price")?.value) || 0;

          let sourceTotal = 0;
          let firstSource = null;

          currentBlock.querySelectorAll(".source_amount").forEach((s) => {
            if (!shouldValidate(s)) return;

            if (!firstSource) firstSource = s;
            if (s.value) sourceTotal += Number(s.value) || 0;
          });

          if (price !== sourceTotal) {
            return showError(
              firstSource,
              `Source total ₹${sourceTotal} must equal price ₹${price}`,
            );
          }
        }

        /* =========================
        CONDITIONAL VALIDATION
      ========================= */

        // DISPOSAL
        const disposal = currentBlock.querySelector(
          '[name="disposal_property[]"]',
        );
        const file = currentBlock.querySelector(
          '[name="disposal_property_attachment[]"]',
        );
        const reason = currentBlock.querySelector(
          '[name="disposal_property_reason[]"]',
        );

        if (shouldValidate(disposal)) {
          if (disposal.value === "Yes") {
            if (file && isVisible(file) && file.files.length === 0) {
              return showError(
                file,
                "Please upload Sanction/intimation attachment",
              );
            }
          }

          if (disposal.value === "No") {
            if (reason && isVisible(reason) && !reason.value.trim()) {
              return showError(
                reason,
                "Please provide reason for Sanction/intimation",
              );
            }
          }
        }

        // RELATIONSHIP
        const rel = currentBlock.querySelector('[name="party_relationship[]"]');
        const relaDesc = currentBlock.querySelector(
          '[name="party_relationship_description[]"]',
        );

        if (shouldValidate(rel) && rel.value === "yes") {
          if (relaDesc && isVisible(relaDesc) && !relaDesc.value.trim()) {
            return showError(
              relaDesc,
              "Please describe relationship with party",
            );
          }
        }

        // DEALING
        const deal = currentBlock.querySelector(
          '[name="applicant_dealing_parties[]"]',
        );
        const dealDesc = currentBlock.querySelector(
          '[name="applicant_dealing_parties_description[]"]',
        );

        if (shouldValidate(deal) && deal.value === "yes") {
          if (dealDesc && isVisible(dealDesc) && !dealDesc.value.trim()) {
            return showError(dealDesc, "Please provide dealing details");
          }
        }
      }

      /* =========================
        CLONE BLOCK
      ========================= */

      const clone = currentBlock.cloneNode(true);

      clone.querySelectorAll("textarea").forEach((el, index) => {
        el.value = "";
        el.id = "editor_" + Date.now() + "_" + index;
      });

      // RESET INPUTS
      clone.querySelectorAll("input").forEach((el) => {
        if (el.type !== "file") el.value = "";
        el.classList.remove("has-error");
      });

      // RESET FILE INPUTS PROPERLY
      clone.querySelectorAll('input[type="file"]').forEach((fileInput) => {
        const newInput = fileInput.cloneNode(true);
        newInput.value = "";
        fileInput.parentNode.replaceChild(newInput, fileInput);
      });

      // RESET SELECTS
      clone.querySelectorAll("select").forEach((el) => {
        el.selectedIndex = 0;
      });

      // KEEP ONLY ONE ROW
      clone
        .querySelectorAll(".row-item-applicant:not(:first-child)")
        .forEach((el) => el.remove());

      clone
        .querySelectorAll(".row-item-source:not(:first-child)")
        .forEach((el) => el.remove());

      // CLEAR TEXT OUTPUT
      clone.querySelectorAll(".price_in_text").forEach((el) => {
        el.textContent = "";
      });

      /* =========================
        FIX VISIBILITY
      ========================= */

      const cloneSource = clone.querySelector(".acquisition_sources");
      const cloneDisposal = clone.querySelector(".disposal_property");

      const currentSource = currentBlock.querySelector(".acquisition_sources");
      const currentDisposal = currentBlock.querySelector(".disposal_property");

      if (currentSource && currentSource.offsetParent !== null) {
        safeShow(cloneSource);
      } else {
        safeHide(cloneSource);
      }

      if (currentDisposal && currentDisposal.offsetParent !== null) {
        safeShow(cloneDisposal);
      } else {
        safeHide(cloneDisposal);
      }

      /* =========================
        RESET DEPENDENT FIELDS
      ========================= */

      // party relationship
      const relDesc = clone.querySelector(
        '[name="party_relationship_description[]"]',
      );

      if (relDesc) {
        relDesc.value = "";
        safeHide(relDesc);
      }

      const relSelect = clone.querySelector('[name="party_relationship[]"]');
      if (relSelect) relSelect.value = "";

      // applicant dealing
      const dealingDesc = clone.querySelector(
        '[name="applicant_dealing_parties_description[]"]',
      );
      if (dealingDesc) {
        dealingDesc.value = "";
        safeHide(dealingDesc);
      }

      const dealingSelect = clone.querySelector(
        '[name="applicant_dealing_parties[]"]',
      );
      if (dealingSelect) dealingSelect.value = "";

      /* =========================
        APPEND BLOCK
      ========================= */

      currentBlock.after(clone);
      updatePropertyButtons();
      toggleAcquiredDisposed();

      if (!isHydratingEdit) {
        clone.scrollIntoView({ behavior: "smooth" });
      }
    }

    /* ===== REMOVE PROPERTY BLOCK ===== */
    if (e.target.closest(".removePropertyBtn")) {
      const blocks = form.querySelectorAll(".property-block");
      const currentBlock = e.target.closest(".property-block");
      if (!currentBlock) return;

      if (blocks.length === 1) {
        alert("At least one property block is required");
        return;
      }

      showConfirm("Do you want to remove this property?").then(
        (isConfirmed) => {
          if (!isConfirmed) return;

          currentBlock.remove();

          updatePropertyButtons();
          toggleAcquiredDisposed();
        },
      );
    }
  });

  /* =========================
     CHANGE EVENTS (FIXED [])
  ========================= */
  form.addEventListener("change", (e) => {
    const target = e.target;
    const block = e.target.closest(".property-block");
    if (!block) return;

    /* ===== disposal ===== */
    if (
      e.target.name === "disposal_property[]" &&
      e.target.tagName === "SELECT"
    ) {
      const file = block.querySelector(".disposal_file_wrapper");
      const reason = block.querySelector(".disposal_reason_wrapper");

      if (e.target.value === "Yes") {
        safeShow(file);
        safeHide(reason);
      } else if (e.target.value === "No") {
        safeShow(reason);
        safeHide(file);
      }
    }

    /* ===== relationship ===== */
    if (
      e.target.name === "party_relationship[]" &&
      e.target.tagName === "SELECT"
    ) {
      const desc = block.querySelector(
        '[name="party_relationship_description[]"]',
      );
      const descBlock = block.querySelector(".party_relationship_description");

      if (!desc) return;

      if (e.target.value === "yes") {
        safeShow(desc);
        safeShow(descBlock);
      } else if (e.target.value === "no" || e.target.value === "") {
        desc.value = "";
        safeHide(desc);
        safeHide(descBlock);
      }
    }

    /* ===== dealing ===== */
    if (
      e.target.name === "applicant_dealing_parties[]" &&
      e.target.tagName === "SELECT"
    ) {
      const desc = block.querySelector(
        '[name="applicant_dealing_parties_description[]"]',
      );
      const descBlock = block.querySelector(
        ".applicant_dealing_parties_description",
      );

      if (!desc) return;

      if (e.target.value === "yes") {
        safeShow(desc);
        safeShow(descBlock);
      } else if (e.target.value === "no" || e.target.value === "") {
        desc.value = "";
        safeHide(desc);
        safeHide(descBlock);
      }
    }

    // validate file upload
    if (
      target.matches(
        'input[type="file"][name="source_document[]"], input[type="file"][name="disposal_property_attachment[]"]',
      )
    ) {
      const file = target.files[0];
      if (!file) return;

      const fileName = file.name.toLowerCase();

      // basic check
      if (!fileName.endsWith(".pdf")) {
        alert("Only PDF files allowed!");
        target.value = "";
        return;
      }

      // signature check (REAL validation)
      const reader = new FileReader();

      reader.onload = function () {
        const arr = new Uint8Array(reader.result).subarray(0, 5);
        let header = "";

        for (let i = 0; i < arr.length; i++) {
          header += String.fromCharCode(arr[i]);
        }

        // PDF signature check
        if (header !== "%PDF-") {
          alert("❌ Invalid PDF file! File content is not PDF.");
          target.value = "";
          return;
        }
      };

      reader.readAsArrayBuffer(file.slice(0, 5));
    }
  });

  form.addEventListener("focusout", (e) => {
    const block = e.target.closest(".property-block");
    if (!block) return;

    const rel = block.querySelector('[name="party_relationship[]"]');
    const relDesc = block.querySelector(
      'textarea[name="party_relationship_description[]"]',
    );

    if (rel && relDesc) {
      relDesc.classList.toggle("d-none", rel.value !== "yes");
    }

    const deal = block.querySelector('[name="applicant_dealing_parties[]"]');
    const dealDesc = block.querySelector(
      'textarea[name="applicant_dealing_parties_description[]"]',
    );

    if (deal && dealDesc) {
      dealDesc.classList.toggle("d-none", deal.value !== "yes");
    }
  });

  function cleanValue(value) {
    return value.replace(/[^a-zA-Z0-9 ,.\-]/g, "");
  }

  /* =========================
   INPUT HANDLER
    ========================= */
  form.addEventListener("input", handleFormInput);

  function handleFormInput(e) {
    // Prevent overwrite during edit hydration
    if (window.__isHydratingEdit) return;

    const target = e.target;
    if (!target) return;

    const block = target.closest(".property-block");
    if (!block) return;

    /* ===== ROUTING ===== */
    if (target.classList.contains("allow-basic")) {
      handleAllowBasic(target);
    }

    if (target.classList.contains("interest_percent")) {
      handleInterestPercent(target, block);
    }

    if (target.classList.contains("property_price")) {
      handlePropertyPrice(target, block);
    }

    if (target.classList.contains("source_amount")) {
      handleSourceAmount(target);
    }

    /* ===== REMOVE INVALID ON TYPE ===== */
    if (target.classList.contains("has-error")) {
      target.classList.remove("has-error");
    }
  }

  function handleAllowBasic(input) {
    const cleaned = input.value.replace(/[^a-zA-Z0-9 ,.\-]/g, "");

    if (input.value !== cleaned) {
      const pos = input.selectionStart;
      input.value = cleaned;

      // prevent cursor jump
      setTimeout(() => {
        input.setSelectionRange(pos - 1, pos - 1);
      }, 0);
    }
  }

  function handleInterestPercent(input, block) {
    let value = input.value.replace(/[^0-9.]/g, "");
    let num = parseFloat(value);

    if (!isNaN(num)) {
      if (num > 100) num = 100;
      if (num < 0) num = 0;
    }

    input.value = value ? num : "";

    const addBtn = block.querySelector(".addRow_applicant");

    if (num === 100) {
      addBtn && addBtn.classList.add("d-none");
    } else {
      addBtn && addBtn.classList.remove("d-none");
    }
  }

  function handlePropertyPrice(input, block) {
    let digits = input.value.replace(/\D/g, "");

    // limit to 9 digits
    if (digits.length > 9) {
      digits = digits.slice(0, 9);
    }

    if (input.value !== digits) {
      input.value = digits;
    }

    const output = block.querySelector(".price_in_text");
    if (!output) return;

    const num = parseFloat(digits);

    if (!digits || isNaN(num)) {
      output.textContent = "";
      output.classList.remove("text-danger", "text-success");
      return;
    }

    output.textContent = `( ${numberToWords(num)} )`;

    if (num > 999999999) {
      output.classList.add("text-danger");
      output.classList.remove("text-success");
    } else {
      output.classList.add("text-success");
      output.classList.remove("text-danger");
    }
  }

  function handleSourceAmount(input) {
    let digits = input.value.replace(/\D/g, "");

    if (digits.length > 9) {
      digits = digits.slice(0, 9);
    }

    input.value = digits;

    const num = parseFloat(digits);

    if (num > 999999999) {
      input.classList.add("has-error");
      input.setCustomValidity("Amount exceeds 99.99 crores limit");
    } else {
      input.classList.remove("has-error");
      input.setCustomValidity("");
    }
  }

  form.addEventListener("keypress", function (e) {
    if (e.target.classList.contains("allow-basic")) {
      if (!/[a-zA-Z0-9 ,\-]/.test(e.key)) {
        e.preventDefault();
      }
    }
  });

  form.addEventListener("paste", function (e) {
    if (e.target.classList.contains("allow-basic")) {
      e.preventDefault();

      let paste = (e.clipboardData || window.clipboardData).getData("text");
      paste = cleanValue(paste);

      document.execCommand("insertText", false, paste);
    }
  });

  handleAcquiredDisposed();
}

function showConfirm(message) {
  return new Promise((resolve) => {
    const $modal = $("#confirmModal");
    const $message = $("#confirmMessage");
    const $okBtn = $("#confirmOkBtn");
    const $cancelBtn = $("#confirmCancelBtn");

    $message.text(message);

    let resolved = false;

    function cleanup(result) {
      if (resolved) return;
      resolved = true;

      $modal.modal("hide");
      resolve(result);
    }

    // Remove old handlers to avoid stacking
    $okBtn.off("click").on("click", () => cleanup(true));
    $cancelBtn.off("click").on("click", () => cleanup(false));

    // Focus cancel button when modal opens
    $modal.off("shown.bs.modal").on("shown.bs.modal", function () {
      $cancelBtn.focus();
    });

    // Handle close (X / backdrop / ESC)
    $modal.off("hidden.bs.modal").on("hidden.bs.modal", function () {
      cleanup(false);
    });

    $modal.modal("show");
  });
}

function prefillApplicantRow(block) {
  if (!block) return;

  const firstRow = block.querySelector(".row-item-applicant");
  if (!firstRow) return;

  const nameInput = firstRow.querySelector('[name="name_applicant[]"]');
  const relationSelect = firstRow.querySelector(
    '[name="relationship_applicant[]"]',
  );

  if (nameInput && !nameInput.value) {
    nameInput.value = sessionStorage.getItem("username") || "";
  }

  if (relationSelect && !relationSelect.value) {
    relationSelect.value = "Self";
  }
}

//handle disabled aquired disposed for property block more then 1
function toggleAcquiredDisposed() {
  const field = document.querySelector('[name="acquired_disposed"]');
  const count = document.querySelectorAll(
    "#property-container .property-block",
  ).length;

  if (!field) return;

  let hidden = document.getElementById("acquired_hidden");

  if (count > 1) {
    field.setAttribute("disabled", "disabled");

    if (!hidden) {
      hidden = document.createElement("input");
      hidden.type = "hidden";
      hidden.name = "acquired_disposed";
      hidden.id = "acquired_hidden";
      field.after(hidden);
    }

    hidden.value = field.value;
  } else {
    field.removeAttribute("disabled");

    if (hidden) hidden.remove();
  }
}

/*=============================
 handel button toggle on property block
==============================*/
function updatePropertyButtons() {
  const blocks = document.querySelectorAll(".property-block");

  blocks.forEach((block, index) => {
    const addBtn = block.querySelector(".addPropertyBtn");
    const removeBtn = block.querySelector(".removePropertyBtn");

    if (addBtn) addBtn.style.display = "none";
    if (removeBtn) removeBtn.style.display = "inline-block";

    // last block → show ADD
    if (index === blocks.length - 1) {
      if (addBtn) addBtn.style.display = "inline-block";
    }

    // first block → hide REMOVE if only one
    if (blocks.length === 1 && removeBtn) {
      removeBtn.style.display = "none";
    }
  });
}

/* =========================
   handle number to word converstion
========================= */
function numberToWords(amount) {
  if (amount > 999999999) {
    return "Amount out of range (exceeds 99.99 crores)";
  }
  const a = [
    "",
    "One",
    "Two",
    "Three",
    "Four",
    "Five",
    "Six",
    "Seven",
    "Eight",
    "Nine",
    "Ten",
    "Eleven",
    "Twelve",
    "Thirteen",
    "Fourteen",
    "Fifteen",
    "Sixteen",
    "Seventeen",
    "Eighteen",
    "Nineteen",
  ];

  const b = [
    "",
    "",
    "Twenty",
    "Thirty",
    "Forty",
    "Fifty",
    "Sixty",
    "Seventy",
    "Eighty",
    "Ninety",
  ];

  function twoDigits(n) {
    return n < 20
      ? a[n]
      : b[Math.floor(n / 10)] + (n % 10 ? " " + a[n % 10] : "");
  }

  function convert(num) {
    let str = "";

    if (Math.floor(num / 10000000)) {
      str += twoDigits(Math.floor(num / 10000000)) + " Crore ";
      num %= 10000000;
    }

    if (Math.floor(num / 100000)) {
      str += twoDigits(Math.floor(num / 100000)) + " Lakh ";
      num %= 100000;
    }

    if (Math.floor(num / 1000)) {
      str += twoDigits(Math.floor(num / 1000)) + " Thousand ";
      num %= 1000;
    }

    if (Math.floor(num / 100)) {
      str += a[Math.floor(num / 100)] + " Hundred ";
      num %= 100;
    }

    if (num) {
      str += (str !== "" ? "and " : "") + twoDigits(num) + " ";
    }

    return str.trim();
  }

  if (!amount || isNaN(amount)) return "Rupees Zero Only";

  let [rupees, paise] = amount.toString().split(".");

  rupees = parseInt(rupees, 10);
  paise = parseInt((paise || "0").padEnd(2, "0").slice(0, 2), 10);

  let result = "";

  // Rupees part
  if (rupees === 0) {
    result = "Rupees Zero";
  } else {
    result = "Rupees " + convert(rupees);
  }

  // Paise part
  if (paise > 0) {
    result += " and " + twoDigits(paise) + " Paise";
  }

  return result + " Only";
}

/* =========================
   MAIN VALIDATION (FORM1)
========================= */
function shouldValidate(field) {
  if (!field) return false;

  if (
    field.type === "button" ||
    field.type === "submit" ||
    field.type === "file"
  )
    return false;

  if (field.disabled) return false;

  if (!isVisible(field)) return false;

  if (field.closest(".d-none")) return false;

  return field.offsetParent !== null && !field.disabled;
}

function showError(field, message) {
  if (!field) return false;

  clearErrors();

  // find parent .form-group
  const formGroup = field.closest(".form-group");

  if (formGroup) {
    formGroup.classList.add("has-error");

    // remove old message
    const oldMsg = formGroup.querySelector(".help-block");
    if (oldMsg) oldMsg.remove();

    // add new message
    const error = document.createElement("span");
    error.className = "help-block";
    error.innerText = message;

    formGroup.appendChild(error);
  }

  // scroll
  field.scrollIntoView({ behavior: "smooth", block: "center" });

  // focus (after scroll)
  setTimeout(() => {
    if (!field.disabled) {
      field.focus();
    }
  }, 300);

  // optional toast/alert
  showAlert(message, "danger");

  return false;
}

function clearErrors() {
  document.querySelectorAll(".form-group.has-error").forEach((el) => {
    el.classList.remove("has-error");
  });

  document.querySelectorAll(".help-block").forEach((el) => {
    el.remove();
  });
}

function isVisible(el) {
  return el && el.offsetParent !== null;
}

function validateForm() {
  const form = document.getElementById("form1");

  clearErrors();

  /* =========================
     BASIC REQUIRED FIELDS
  ========================= */
  for (let field of form.querySelectorAll("input, select, textarea")) {
    if (!shouldValidate(field)) continue;

    // checkbox
    if (field.type === "checkbox") {
      if (field.required && !field.checked) {
        return showError(field, "Please accept the declaration");
      }
      continue;
    }

    // required
    if (field.required) {
      if (!field.value || field.value.trim() === "") {
        console.log(field.name);
        return showError(field, "Please fill the required field");
      }
    }
  }

  /* =========================
    PRICE VALIDATION
  ========================= */
  for (let block of form.querySelectorAll(".property-block")) {
    const priceInput = block.querySelector(".property_price");

    if (!shouldValidate(priceInput)) continue;

    const value = priceInput.value.trim();
    const price = Number(value);

    // Empty
    if (!value) {
      return showError(priceInput, "Please enter property value");
    }

    // Not a number
    if (isNaN(price)) {
      return showError(priceInput, "Price must be a valid number");
    }

    // Negative or zero
    if (price <= 0) {
      return showError(priceInput, "Price must be greater than 0");
    }

    // Max limit (99 crore)

    if (price > 999999999) {
      return showError(priceInput, "Amount exceeds 99 crore limit");
    }
  }

  /* =========================
     INTEREST VALIDATION
  ========================= */
  for (let block of form.querySelectorAll(".property-block")) {
    const inputs = block.querySelectorAll(".interest_percent");

    let total = 0;
    let hasValue = false;

    inputs.forEach((input) => {
      if (!shouldValidate(input)) return;

      if (input.value) {
        hasValue = true;
        total += Number(input.value) || 0;
      }
    });

    if (hasValue && total !== 100) {
      return showError(
        inputs[0],
        `Total interest must be 100% (current: ${total}%)`,
      );
    }
  }

  /* =========================
     SOURCE VALIDATION
  ========================= */
  for (let block of form.querySelectorAll(".property-block")) {
    const wrapper = block.querySelector(".acquisition_sources");

    if (wrapper && !wrapper.classList.contains("d-none")) {
      const price = Number(block.querySelector(".property_price")?.value) || 0;

      let total = 0;
      let hasSource = false;
      let firstInput = null;

      block.querySelectorAll(".source_amount").forEach((input) => {
        if (!shouldValidate(input)) return;

        if (!firstInput) firstInput = input;

        if (input.value) {
          hasSource = true;
          total += Number(input.value) || 0;
        }
      });

      if (hasSource && price !== total) {
        return showError(
          firstInput,
          `Source total ₹${total} must equal price ₹${price}`,
        );
      }
    }
  }

  /* =========================
     CONDITIONAL VALIDATION
  ========================= */
  for (let block of form.querySelectorAll(".property-block")) {
    // DISPOSAL
    const disposal = block.querySelector('[name="disposal_property[]"]');
    const file = block.querySelector('[name="disposal_property_attachment[]"]');
    const reason = block.querySelector('[name="disposal_property_reason[]"]');

    if (shouldValidate(disposal)) {
      if (disposal.value === "Yes") {
        if (file && isVisible(file) && file.files.length === 0) {
          return showError(
            file,
            "Please upload Sanction/intimation attachment",
          );
        }
      }

      if (disposal.value === "No") {
        if (reason && isVisible(reason) && !reason.value.trim()) {
          return showError(
            reason,
            "Please provide reason for Sanction/intimation",
          );
        }
      }
    }

    // RELATIONSHIP
    const rel = block.querySelector('[name="party_relationship[]"]');
    const relDesc = block.querySelector(
      '[name="party_relationship_description[]"]',
    );

    if (shouldValidate(rel) && rel.value === "yes") {
      if (relDesc && isVisible(relDesc) && !relDesc.value.trim()) {
        return showError(relDesc, "Please describe relationship with party");
      }
    }

    // DEALING
    const deal = block.querySelector('[name="applicant_dealing_parties[]"]');
    const dealDesc = block.querySelector(
      '[name="applicant_dealing_parties_description[]"]',
    );

    if (shouldValidate(deal) && deal.value === "yes") {
      if (dealDesc && isVisible(dealDesc) && !dealDesc.value.trim()) {
        return showError(dealDesc, "Please provide dealing details");
      }
    }
  }

  return true;
}

/* =========================
   SUBMIT
========================= */
function generateKey(prefix) {
  return `${prefix}_${Date.now()}_${Math.random().toString(36).substring(2, 8)}`;
}

function createPayload() {
  const properties = [];
  const files = [];

  document.querySelectorAll(".property-block").forEach((block) => {
    /* ================= DISPOSAL FILE ================= */
    const fileInput = block.querySelector(
      '[name="disposal_property_attachment[]"]',
    );

    let disposalFileKey = null;

    if (fileInput?.files?.length > 0) {
      const file = fileInput.files[0];
      disposalFileKey = generateKey("disposal");

      files.push({
        key: disposalFileKey,
        file: file,
      });
    } else if (fileInput?.dataset?.existingFileKey) {
      disposalFileKey = fileInput.dataset.existingFileKey;
    }

    const property = {
      property_location:
        block.querySelector('[name="property_location[]"]')?.value || "",

      property_description:
        block.querySelector('[name="property_description[]"]')?.value || "",

      property_hold:
        block.querySelector('[name="property_hold[]"]')?.value || "",

      property_price:
        parseFloat(block.querySelector('[name="property_price[]"]')?.value) ||
        0,

      disposal_property:
        block.querySelector('[name="disposal_property[]"]')?.value || "",

      disposal_property_reason:
        block.querySelector('[name="disposal_property_reason[]"]')?.value || "",

      disposal_property_attachment: disposalFileKey,

      party_name: block.querySelector('[name="party_name[]"]')?.value || "",

      party_address:
        block.querySelector('[name="party_address[]"]')?.value || "",

      party_relationship:
        block.querySelector('[name="party_relationship[]"]')?.value || "",

      party_relationship_description:
        block.querySelector('[name="party_relationship_description[]"]')
          ?.value || "",

      applicant_dealing_parties:
        block.querySelector('[name="applicant_dealing_parties[]"]')?.value ||
        "",

      applicant_dealing_parties_description:
        block.querySelector('[name="applicant_dealing_parties_description[]"]')
          ?.value || "",

      party_transaction_mode:
        block.querySelector('[name="party_transaction_mode[]"]')?.value || "",

      applicants: [],
      sources: [],
    };

    /* ================= APPLICANTS ================= */
    block.querySelectorAll(".row-item-applicant").forEach((row) => {
      const name = row.querySelector('[name="name_applicant[]"]')?.value;
      const interest = row.querySelector(
        '[name="interest_applicant[]"]',
      )?.value;
      const relationship = row.querySelector(
        '[name="relationship_applicant[]"]',
      )?.value;

      if (!name && !interest && !relationship) return;

      property.applicants.push({
        name: name || "",
        interest: parseFloat(interest) || 0,
        relationship: relationship || "",
      });
    });

    /* ================= SOURCES (FIXED) ================= */
    block.querySelectorAll(".row-item-source").forEach((row) => {
      const name = row.querySelector('[name="source_name[]"]')?.value;
      const amountVal = row.querySelector('[name="source_amount[]"]')?.value;
      const amount = parseFloat(amountVal);

      const fileInput = row.querySelector('[name="source_document[]"]');

      let fileKey = null;

      if (fileInput?.files?.length > 0) {
        const file = fileInput.files[0];
        fileKey = generateKey("source");

        files.push({
          key: fileKey,
          file: file,
        });
      } else if (fileInput?.dataset?.existingFileKey) {
        fileKey = fileInput.dataset.existingFileKey;
      }

      if (!name && !amountVal && !fileKey) return;

      property.sources.push({
        name: name || "",
        amount: !isNaN(amount) ? amount : 0,
        file_key: fileKey,
      });
    });

    properties.push(property);
  });

  return { properties, files };
}

function openConfirmModal(message, onConfirm) {
  const modal = $("#confirmModal");
  $("#confirmMessage").text(message);

  modal.modal("show");

  const okBtn = document.getElementById("confirmOkBtn");

  const newOkBtn = okBtn.cloneNode(true);
  okBtn.replaceWith(newOkBtn);

  newOkBtn.addEventListener("click", () => {
    modal.modal("hide");
    if (typeof onConfirm === "function") onConfirm();
  });
}

function getEditFormId() {
  // 1. If edit mode (already loaded form)
  if (window.__editFormData?.id) {
    return String(window.__editFormData.id);
  }

  // 2. If ID exists in URL
  const params = new URLSearchParams(window.location.search);
  const urlId = params.get("id");
  if (urlId) {
    return urlId;
  }

  // 3. Fallback to draftId in localStorage (for newly created draft forms)
  const draftId = localStorage.getItem("draftId");
  if (draftId) {
    return draftId;
  }

  return null;
}

function fillPropertyBlock(block, property) {
  if (!block || !property) return;

  const setValue = (selector, value) => {
    const field = block.querySelector(selector);
    if (!field || value === null || value === undefined) return;
    field.value = value;
    field.dispatchEvent(new Event("change", { bubbles: true }));
  };

  setValue('[name="property_location[]"]', property.property_location);
  setValue('[name="property_description[]"]', property.property_description);
  setValue('[name="property_hold[]"]', property.property_hold);
  setValue('[name="property_price[]"]', property.property_price);
  setValue('[name="disposal_property[]"]', property.disposal_property);
  setValue(
    '[name="disposal_property_reason[]"]',
    property.disposal_property_reason,
  );
  setValue('[name="party_name[]"]', property.party_name);
  setValue('[name="party_address[]"]', property.party_address);
  setValue('[name="party_relationship[]"]', property.party_relationship);
  setValue(
    '[name="party_relationship_description[]"]',
    property.party_relationship_description,
  );
  setValue(
    '[name="applicant_dealing_parties[]"]',
    property.applicant_dealing_parties,
  );
  setValue(
    '[name="applicant_dealing_parties_description[]"]',
    property.applicant_dealing_parties_description,
  );
  setValue('[name="nature_dealing_party[]"]', property.nature_dealing_party);
  setValue(
    '[name="party_transaction_mode[]"]',
    property.party_transaction_mode,
  );

  // Existing disposal attachment link (edit mode)
  const disposalInput = block.querySelector(
    '[name="disposal_property_attachment[]"]',
  );
  const disposalLinkWrap = block.querySelector(
    ".js-existing-disposal-attachment",
  );
  if (disposalInput) {
    delete disposalInput.dataset.existingFileKey;
  }
  if (disposalLinkWrap) {
    disposalLinkWrap.classList.add("d-none");
    disposalLinkWrap.innerHTML = "";
  }
  if (property.disposal_attachment?.download_url && disposalInput) {
    disposalInput.dataset.existingFileKey =
      property.disposal_attachment.file_key || "";
    if (disposalLinkWrap) {
      const key = property.disposal_attachment.file_key;
      const name =
        property.disposal_attachment.file_name || "Download attachment";
      disposalLinkWrap.innerHTML = `<a href="../api/view_attachement_file.php?file_key=${key}" target="_blank" rel="noopener"> ${name}</a>`;
      disposalLinkWrap.classList.remove("d-none");
    }
  }

  if (Array.isArray(property.applicants) && property.applicants.length) {
    const addApplicantBtn = block.querySelector(".addRow_applicant");
    for (let i = 1; i < property.applicants.length; i++) {
      addApplicantBtn?.click();
    }

    const applicantRows = block.querySelectorAll(".row-item-applicant");
    property.applicants.forEach((applicant, idx) => {
      const row = applicantRows[idx];
      if (!row) return;
      const name = row.querySelector('[name="name_applicant[]"]');
      const interest = row.querySelector('[name="interest_applicant[]"]');
      const rel = row.querySelector('[name="relationship_applicant[]"]');
      if (name) name.value = applicant.name || "";
      if (interest) interest.value = applicant.interest || "";
      if (rel) rel.value = applicant.relationship || "";
    });
  }

  if (Array.isArray(property.sources) && property.sources.length) {
    const addSourceBtn = block.querySelector(".addRow_source");
    for (let i = 1; i < property.sources.length; i++) {
      addSourceBtn?.click();
    }

    const sourceRows = block.querySelectorAll(".row-item-source");
    property.sources.forEach((source, idx) => {
      const row = sourceRows[idx];
      if (!row) return;
      const sourceName = row.querySelector('[name="source_name[]"]');
      const sourceAmount = row.querySelector('[name="source_amount[]"]');
      const sourceFile = row.querySelector('[name="source_document[]"]');
      const linkWrap = row.querySelector(".js-existing-source-attachment");
      if (sourceName) sourceName.value = source.name || "";
      if (sourceAmount) sourceAmount.value = source.amount || "";

      if (sourceFile) {
        delete sourceFile.dataset.existingFileKey;
      }
      if (linkWrap) {
        linkWrap.classList.add("d-none");
        linkWrap.innerHTML = "";
      }
      if (source?.attachment?.download_url && sourceFile) {
        sourceFile.dataset.existingFileKey = source.attachment.file_key || "";
        if (linkWrap) {
          const key = source.attachment.file_key;
          const name = source.attachment.file_name || "Download attachment";
          linkWrap.innerHTML = `<a href="../api/view_attachement_file.php?file_key=${key}" target="_blank" rel="noopener">Existing file: ${name}</a>`;
          linkWrap.classList.remove("d-none");
        }
      }
    });
  }
}

function hydrateForm1ForEdit() {
  const form = document.getElementById("form1");
  const data = window.__editFormData;
  const ft = String(data?.form_type || "").toLowerCase();
  if (!form || !data || ft !== "immovable") return;

  const propertyList = Array.isArray(data.properties)
    ? data.properties
    : Array.isArray(data.propertyDetails)
      ? data.propertyDetails
      : [];

  const setMainValue = (name, value) => {
    const field = form.querySelector(`[name="${name}"]`);
    if (!field || value === null || value === undefined) return;
    field.value = value;
    field.dispatchEvent(new Event("change", { bubbles: true }));
  };

  setMainValue("purpose", data.purpose);
  setMainValue("acquired_disposed", data.acquired_disposed);
  setMainValue("date_acquisition_disposed", data.date_acquisition_disposed);
  setMainValue("mode_acquisition", data.mode_acquisition);
  setMainValue("mode_acquisition_other", data.mode_acquisition_other);
  setMainValue("mode_disposal", data.mode_disposal);
  setMainValue("mode_disposal_other", data.mode_disposal_other);
  setMainValue("acquisition_gift", data.acquisition_gift);
  setMainValue("other_relevant", data.other_relevant);

  if (propertyList.length) {
    window.__isHydratingEdit = true;
    try {
      // Ensure block count matches API payload count before filling values.
      let safety = 0;
      while (
        form.querySelectorAll(".property-block").length < propertyList.length &&
        safety < propertyList.length + 5
      ) {
        const beforeCount = form.querySelectorAll(".property-block").length;
        if (typeof window.__appendPropertyBlockForm1 === "function") {
          window.__appendPropertyBlockForm1();
        }
        const afterCount = form.querySelectorAll(".property-block").length;
        if (afterCount <= beforeCount) break;
        safety++;
      }

      propertyList.forEach((property, idx) => {
        const blocks = form.querySelectorAll(".property-block");
        const currentBlock = blocks[idx];
        if (!currentBlock) return;

        fillPropertyBlock(currentBlock, property);
      });
    } finally {
      window.__isHydratingEdit = false;
    }
  }
}

function submitForm() {
  if (!validateForm()) return;

  openConfirmModal("Are you sure you want to submit the form?", async () => {
    const form = document.getElementById("form1");

    const { properties, files } = createPayload();

    const formData = new FormData(form);

    formData.append("propertyDetails", JSON.stringify(properties));

    files.forEach((f) => {
      formData.append(f.key, f.file);
    });

    formData.append("form_type", "immovable");
    formData.append("form_status", 1); // final submit

    // get draft/edit id
    const editFormId = getEditFormId();
    if (editFormId) {
      formData.append("form_id", editFormId);
    }

    try {
      const res = await fetch("../api/submit_form.php", {
        method: "POST",
        body: formData,
      });

      if (!res.ok) {
        throw new Error(`Request failed with status ${res.status}`);
      }

      const json = await res.json();

      if (json.success) {
        // IMPORTANT: clear draft after final submit
        localStorage.removeItem("draftId");

        sessionStorage.setItem("alertMsg", "Form submitted successfully!");
        showAlert("Form submitted successfully!", "success");

        window.location.href = "requestLists.php";
      } else {
        const errorMessage = json.message || json.error || "Submission failed";

        showAlert(errorMessage, "danger");
      }
    } catch (err) {
      console.error(err);
      alert("Server error");
    }
  });
}
function saveDraft() {
  const form = document.getElementById("form1");
  if (!form) {
    alert("Form not found");
    return;
  }

  const acquiredDisposedField =
    form.querySelector('[name="acquired_disposed"]') ||
    document.getElementById("acquired_hidden");
  const acquiredDisposedValue = (acquiredDisposedField?.value || "").trim();

  const purposeField = form.querySelector('[name="purpose"]');
  const purposeValue = (purposeField?.value || "").trim();
  if (!purposeValue) {
    showError(
      purposeField || form,
      "Please select purpose and Acquired/Disposed before saving draft.",
    );
    return;
  }

  if (!acquiredDisposedValue) {
    showError(
      acquiredDisposedField || form,
      "Please select Acquired/Disposed before saving draft.",
    );
    return;
  }

  openConfirmModal(
    "Are you sure you want to save this form as a draft?",
    async () => {
      const { properties, files } = createPayload();
      const formData = new FormData(form);

      formData.append("propertyDetails", JSON.stringify(properties));

      files.forEach((f) => {
        formData.append(f.key, f.file);
      });

      formData.append("form_type", "immovable");
      formData.append("form_status", 0);
      const editFormId = getEditFormId();
      if (editFormId) {
        formData.append("form_id", editFormId);
      }

      try {
        const res = await fetch("../api/submit_form.php", {
          method: "POST",
          body: formData,
        });

        if (!res.ok) {
          throw new Error(`Request failed with status ${res.status}`);
        }

        const json = await res.json();

        if (json.success) {
          if (json.form_id) {
            localStorage.setItem("draftId", json.form_id);
          }
          showAlert("Draft saved successfully!", "success");
          // location.reload();
        } else {
          const errorMessage =
            json.message || json.error || "Draft could not be saved";
          showAlert(errorMessage, "danger");
          alert(errorMessage);
        }
      } catch (err) {
        console.error(err);
        alert("Server error while saving draft");
      }
    },
  );
}
