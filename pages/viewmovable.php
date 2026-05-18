<?php require '../connection/session_check.php'; ?>
<!DOCTYPE html>
<html>

<?php require 'head.php'; ?>

<body class="hold-transition skin-blue sidebar-mini">
    <!-- Site wrapper -->
    <div class="wrapper">

        <?php require 'header.php'; ?>
        <!-- =============================================== -->

        <?php require 'sidebar.php'; ?>

        <!-- =============================================== -->

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">

            <div id="msg" style="position: fixed; top:20px; right:20px; z-index:9999; width:320px;"></div>

            <!-- Content Header (Page header) -->
            <section class="content-header">
                <h1>
                    View Movable Property
                </h1>
                <ol class="breadcrumb">
                    <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Home</a></li>
                    <li>Property</li>
                    <li class="active">View Movable property</li>
                </ol>
            </section>

            <!-- Main content -->
            <section class="content">
                <form id="form1" role="form" novalidate enctype="multipart/form-data">
                    <div id="print">
                        <!-- Header -->
                        <div class="row">
                            <div class="col-md-1"></div>
                            <div class="col-md-10">
                                <div class="text-center" style="margin-bottom:15px;">
                                    <p style="text-align:center;font-weight:bold;">
                                        प्रपत्र-II / FORM-II
                                    </p>
                                    <p class="text-center mt-1">
                                        चल संपत्ति के संबंध में लेन-देन हेतु केंद्रीय सिविल सेवा (आचरण) नियमावली, 1964 के नियम 18 (3) के अंतर्गत पूर्व सूचना देने या पूर्व मंजूरी लेने के लिए प्रपत्र।<br>
                                        Form for giving prior intimation or seeking previous sanction under Rule 18 (3) of the CCS (Conduct) Rules, 1964 for transaction in respect of movable property.
                                    </p>
                                </div>

                            </div>
                            <div class="col-md-1 d-flex align-items-center justify-content-center">
                                <span class="fa fa-print custom-print-icon" onclick="handlePrint()" title="Print"></span>
                            </div>
                        </div>

                        <div class="box box-solid box-primary">

                            <div class="box-header with-border">
                                <h4 class="box-title">Employee Details</h4>
                            </div>
                            <div class="box-body">

                                <div class="row">

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="bold-label">Name of the Government servant</label>
                                            <input type="text" name="username" class="form-control" readonly>
                                        </div>
                                    </div>


                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="bold-label">Designation</label>
                                            <input type="text" name="designation" class="form-control" readonly>
                                        </div>
                                    </div>


                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="bold-label">Service to which belongs </label>
                                            <input type="text" name="service" class="form-control" readonly>
                                        </div>
                                    </div>


                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="bold-label">Employee No. / Code No.</label>
                                            <input type="text" name="emp_code" class="form-control" readonly>
                                        </div>
                                    </div>


                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="bold-label"> Scale of Pay and present pay </label>
                                            <input type="text" name="payscale" class="form-control" readonly>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- Property Section -->
                        <div id="property-container">

                            <div class="box box-solid box-primary property-block">

                                <div class="box-header with-border">
                                    <h4 class="box-title">Description of Property</h4>
                                </div>

                                <div class="box-body">

                                    <div class="row">

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="bold-label">Purpose of application</label>
                                                <input type="text" name="purpose" class="form-control" readonly>
                                            </div>
                                        </div>


                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="bold-label">Whether property is being acquired or disposed of</label>
                                                <input type="text" name="acquisition_disposed" class="form-control" readonly>
                                            </div>
                                        </div>


                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="bold-label" id="date_acquisition_disposed">Probable date of acquisition/disposal of property</label>
                                                <input type="text" name="date_acquisition_disposed" class="form-control" readonly>
                                            </div>
                                        </div>

                                        <div class="col-md-4" id="mode_acquisition">
                                            <div class="form-group">
                                                <label class="bold-label">Mode of acquisition</label>
                                                <input type="text" name="mode_acquisition" class="form-control" readonly>
                                                <textarea name="mode_acquisition_other" class="form-control mt-2 d-none" placeholder="Text here..." readonly></textarea>
                                            </div>
                                        </div>

                                        <div class="col-md-4" id="mode_disposal">
                                            <div class="form-group">
                                                <label class="bold-label">Mode of disposal</label>
                                                <input type="text" name="mode_disposal" class="form-control" readonly>
                                                <textarea name="mode_disposal_other" class="form-control mt-2 d-none" placeholder="Text here..." readonly></textarea>
                                            </div>
                                        </div>

                                    </div>
                                    <!--./row-->

                                    <div id="property-lists"></div>

                                </div>
                            </div>
                        </div>

                        <div class="box box-solid box-primary">
                            <div class="box-header with-border">
                                <h4 class="box-title"> Declarations</h4>
                            </div>
                            <div class="box-body">

                                <!-- Gift + Other Info -->
                                <div class="row">

                                    <!-- Acquisition Gift -->
                                    <div class="col-md-6 d-none" id="acquisition_gift">
                                        <div class="form-group">
                                            <label>Sanction is also required under rule 13 of the CCs(Conduct) Rules, 1964?</label>
                                            <input type="text" name="acquisition_gift" class="form-control" readonly>
                                        </div>
                                    </div>

                                    <!-- Other Relevant -->
                                    <div class="col-md-6" id="other_relevant">
                                        <div class="form-group">
                                            <label>Any other relevant fact which the applicant may like to mention</label>
                                            <textarea class="form-control" readonly name="other_relevant" placeholder="Text here..."></textarea>
                                        </div>
                                    </div>

                                    <div class="d-none col-md-12" id="form2_inparts">
                                        I, <strong class="emp_name_text"></strong> hereby declare that the particulars given above are true.
                                        I request that I may be given permission to
                                        <span class="acquired_disposed_value"></span>
                                        the property as described above from/to
                                        <strong class="party_name_text"></strong>,
                                        whose name is mentioned in details of the Parties.
                                    </div>

                                    <!-- Full -->
                                    <div class="d-none col-md-12" id="form2_full">
                                        I, <strong class="emp_name_text"></strong> hereby intimate the proposed <span class="acquisition_disposed_value"></span> of property by me as detailed above. I declare that the particulars given above are true.
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="box box-primary" id="status">
                        <div class="box-header with-border">
                            <h4 class="box-title"> Action / Remarks</h4>
                        </div>
                        <div class="box-body">

                            <div class="row">
                                <div class="col-md-3" id="correctOM-container">
                                    <input class="form-check-input" type="checkbox" name="correctOM" id="correctOM"> OM found correct.
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 " id='remark'>
                                    <div class="form-group">
                                        <label>Remarks:</label>
                                        <textarea id="remarks" name="remarks" class="form-control"></textarea>
                                    </div>
                                </div>

                                <div class="col-md-6" id="forwardId">
                                    <div class="form-group">
                                        <label>Select Employee to forward:</label>
                                        <select id="employee" class="form-control" name="employee">
                                            <option value="">Select...</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-4">
                                <div class="col-md-12 d-flex justify-content-center gap-2">
                                    <button type="button" class="btn btn-primary" id="generatebtn" onclick="generateCertificate()">Generate Certificate</button>
                                    <button type="button" class="btn btn-danger" id="revertbtn" onclick="handleRevert()">Reject</button>
                                    <button type="button" class="btn btn-success" id="forwardbtn" onclick="handleforward()">Forward</button>
                                    <button type="button" class="btn btn-primary" id="OMbtn" onclick="openMemo()">View OM</button>
                                </div>
                            </div>

                        </div>
                    </div>

                </form>
                <!-- /.form -->
            </section>
            <!-- /.content -->
        </div>
        <!-- /.content-wrapper -->
    </div>
    <!-- ./wrapper -->

    <div class="modal fade" id="previewModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span></button>
                    <h4 class="modal-title">File Preview</h4>
                </div>

                <div class="modal-body p-0" style="height:80vh">
                    <iframe id="previewFrame"> </iframe>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="memo" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span></button>
                    <h4 class="modal-title">Memo Preview</h4>
                </div>
                <!-- BODY -->
                <div class="modal-body ">
                    <div class="box box-primary box-solid p-4">
                        <div id="memoContent"></div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php require 'footer.php'; ?>

    <script>
        let usersCache = [];
        let formId = null;
        let data = [];

        async function LoadEmployees() {
            try {
                const res = await fetch('../api/get_usersData.php');
                const json = await res.json();

                usersCache = json.success ? json.data : [];

                populateEmployeeDropdown();

            } catch (err) {
                console.error("Error loading immovable:", err);
            }
        }

        async function loadmovableById(id) {
            try {
                const res = await fetch('../api/getDataById.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'Application/json'
                    },
                    body: JSON.stringify({
                        id: id
                    })
                })

                const json = await res.json();
                data = json.data;
                prefillForm(data);

            } catch (error) {
                console.log("Error", error)
            }
        }

        function getQueryParam(name) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(name);
        }

        function prefillForm(data) {

            // simple fields
            document.querySelector('[name="username"]').value = data.user_details.username ?? "";
            document.querySelector('[name="designation"]').value = data.user_details.designation ?? "";
            document.querySelector('[name="service"]').value = data.user_details.service ?? "";
            document.querySelector('[name="emp_code"]').value = data.user_details.emp_code ?? "";
            document.querySelector('[name="payscale"]').value = data.user_details.payscale ?? "";
            document.querySelector('[name="purpose"]').value = data.purpose ?? "";
            document.querySelector('[name="acquisition_disposed"]').value = data.acquired_disposed ?? "";
            document.querySelector('[name="date_acquisition_disposed"]').value = data.date_acquisition_disposed ?? "";
            document.querySelector('[name="mode_disposal"]').value = data.mode_disposal ?? "";
            document.querySelector('[name="mode_acquisition"]').value = data.mode_acquisition ?? "";
            document.querySelector('[name="acquisition_gift"]').value = data.acquisition_gift ?? "";
            document.querySelector('[name="other_relevant"]').value = data.other_relevant ?? "";

            if (!data.other_relevant) {
                document.getElementById("other_relevant").classList.add('d-none')
            }

            if (data.acquired_disposed === 'disposal') {
                document.getElementById('mode_acquisition').classList.add('d-none');
                document.getElementById('mode_disposal').classList.remove('d-none')
            } else {
                document.getElementById('mode_acquisition').classList.remove('d-none');
                document.getElementById('mode_disposal').classList.add('d-none')
            }

            if (data.acquisition_gift !== '') {
                document.getElementById('acquisition_gift').classList.remove('d-none');
            } else {
                document.getElementById('acquisition_gift').classList.add('d-none');
            }

            if (data.purpose === 'Sanction for transaction') {
                document.getElementById('form2_inparts').classList.remove('d-none');
                document.getElementById('form2_full').classList.add('d-none')
            } else {
                document.getElementById('form2_inparts').classList.add('d-none');
                document.getElementById('form2_full').classList.remove('d-none')
            }

            data.properties.forEach((property, index) => {
                renderPropertyPreview(data.acquired_disposed, property, index);
            });

            const correctOM = document.getElementById('correctOM');
            correctOM.checked = Number(data.correctom) === 1;
            // disable if prefilled
            if (Number(data.correctom) === 1) {
                correctOM.disabled = true;
            } else {
                if (sessionStorage.getItem('designation') === "DDG") {
                    document.getElementById('correctOM-container').classList.add('d-none')
                }
            }

            correctOM.checked = data.correctom == 1;

            if (sessionStorage.getItem('designation') === 'SO') {
                document.getElementById('generatebtn').classList.add('d-none');
            }

            if (sessionStorage.getItem('designation') == 'DH') {
                document.getElementById('generatebtn').classList.add('d-none');
                document.getElementById('revertbtn').classList.add('d-none');
            }

            if (sessionStorage.getItem('designation') == 'JD') {
                document.getElementById('generatebtn').classList.add('d-none');
            }

            if (sessionStorage.getItem('username') === data?.user_details?.username) {
                document.getElementById('status').classList.add('d-none')
            }

            populateEmployeeDropdown();

        }

        async function generateCertificate() {
            try {
                const res = await fetch('../api/generate_pdf_movable.php', {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        id: formId
                    })
                });

                if (!res.ok) {
                    throw new Error("Failed to generate PDF");
                }

                const blob = await res.blob();

                // Create download link
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement("a");
                a.href = url;
                a.download = `Certificate.pdf`;
                document.body.appendChild(a);
                a.click();

                // Cleanup
                a.remove();
                window.URL.revokeObjectURL(url);

            } catch (err) {
                alert("Failed to generate certificate");
            }
        }

        function renderPropertyPreview(acquired_disposed, property, index) {
            const container = document.getElementById("property-lists");

            const html = `
                    <div class="box box-solid box-info no-break">
                        <div class="box-header with-border">
                            <h4 class="box-title">Property ${index + 1}</h4>    
                        </div>
                        <div class="box-body">  

                            <div class="row mt-3">
                                
                                <div class="col-md-4">
                                    <strong>Details of Property:</strong><br>
                                    ${property.property_description}
                                </div>

                                <div class="col-md-4">
                                    <strong>Property Price:</strong><br>
                                    ₹ ${property.property_price}
                                </div>

                            </div>

                            <div class="mt-3">
                                <strong>Applicants :</strong>
                                <table class="table table-bordered">
                                    <tr>
                                    <th>Name</th>
                                    <th>Interest (%)</th>
                                    <th>Relationship</th>
                                    </tr>
                                    ${property.applicants
                                    .map(
                                        (a) =>
                                        `
                                        <tr>
                                            <td>${a.name} </td>
                                            <td>${a.interest}</td>
                                            <td>${a.relationship}</td>
                                        </tr>`,
                                    )
                                    .join("")}
                                </table>
                            </div>

                            ${
                            acquired_disposed !== "disposal"
                                ? `
                                <div class="mt-3">
                                    <strong>Sources :</strong>
                                    <table class="table table-bordered">
                                        <tr>
                                    <th>Name</th>
                                        <th>Amount (₹)</th>
                                        <th>Attachment</th>           
                                        </tr>
                                        ${property.sources
                                            .map(
                                                (a) => `
                                                <tr>
                                                    <td>${a.name}</td>
                                                    <td>${a.amount}</td>
                                                    <td>
                                                        ${
                                                        a.attachment
                                                            ? `
                                                            <button type="button"
                                                                class="btn btn-outline-primary btn-sm"
                                                                title="Preview File"
                                                                onclick="openPreview('${a.attachment.file_key}')">
                                                                <i class="fa fa-eye"></i>
                                                            </button>

                                                            <a href="../api/view_attachement_file.php?file_key=${a.attachment.file_key}&mode=download"
                                                                class="btn btn-sm btn-outline-primary ms-2"
                                                                download>
                                                                <i class="fa fa-download"></i>
                                                            </a>
                                                            `
                                                            : `<span class="text-muted">No file</span>`
                                                        }
                                                    </td>
                                                </tr>`
                                            )
                                        .join("")}
                                    </table>
                                </div>`
                                : `
                                <div class="mt-3">
                                    <strong>Sanction/intimation Status : </strong><br>
                                        ${property.disposal_property === "No" ?
                                            property.disposal_property + ", " + property.disposal_property_reason 
                                            :
                                            property.disposal_property  +  " " + (
                                            property.disposal_attachment?.file_key
                                            ? `<button type="button" class="btn btn-outline-primary btn-sm"
                                                    title="Preview File"
                                                    onclick="openPreview('${property.disposal_attachment.file_key}')">
                                                    View Attachment <i class="fa fa-eye"></i>
                                                </button>`
                                            : ""
                                        )}
                                </div>
                                `
                            }

                            <div class="row mt-3">

                                <div class="col-md-6">
                                    <strong>Party Name:</strong><br>
                                    ${property.party_name}
                                </div>

                                <div class="col-md-6">
                                    <strong>Address:</strong><br>
                                    ${property.party_address}
                                </div>

                                <div class="col-md-6 mt-2">
                                    <strong>Party related to the applicant.</strong><br>
                                    ${
                                        property.party_relationship === "yes"
                                        ? property.party_relationship +
                                            ", " +
                                            property.party_relationship_description
                                        : property.party_relationship
                                    }
                                </div>

                                <div class="col-md-6 mt-2">
                                    <strong>Did the applicant have any official dealing with the parties.</strong><br>
                                    ${
                                        property.applicant_dealing_parties === "yes"
                                        ? property.applicant_dealing_parties +
                                            ", " +
                                            property.applicant_dealing_parties_description
                                        : property.applicant_dealing_parties
                                    }
                                </div>
                                <div class="col-md-6 mt-2">
                                    <strong>Nature of official dealing with the party</strong><br>
                                    ${property.nature_dealing_party}
                                </div>

                                <div class="col-md-6 mt-2">
                                    <strong>How was the transaction arranged.</strong><br>
                                    ${property.party_transaction_mode}
                                </div>

                            </div>
                        </div>
                    </div>
                `;

            container.insertAdjacentHTML("beforeend", html);
        }

        function openPreview(fileKey) {
            const iframe = document.getElementById("previewFrame");
            iframe.src = `../api/view_attachement_file.php?file_key=${fileKey}&mode=preview`;

            $('#previewModal').modal('show');
        }

        function openMemo() {
            const currentDate = new Date().toLocaleDateString('en-GB');

            /* ================= PROPERTY PARAGRAPHS ================= */

            let propertyText = "";

            if (data.properties.length === 1) {

                /* ================= SINGLE PROPERTY FORMAT ================= */

                const property = data.properties[0];

                const applicantsText = property.applicants.length > 0 ?
                    property.applicants.map(a =>
                        `${a.interest}% ${a.relationship} (${a.name})`
                    ).join(" and ") :
                    "";

                let sourceText = "";

                if (property.sources && property.sources.length > 0) {
                    const sourcesFormatted = property.sources.map(s =>
                        `₹${Number(s.amount).toLocaleString('en-IN')}/-`
                    );

                    sourceText = `
                        The funds for the said purchase were arranged through 
                        ${sourcesFormatted.join(" and ")}.
                    `;
                }

                propertyText = `
                    <p style="text-align:justify;">
                        The movable property, i.e., ${property.property_description}, 
                        valued at ₹${Number(property.property_price).toLocaleString('en-IN')}/-,
                        is held by ${applicantsText}.
                    </p>

                    <p style="text-align:justify;">
                        The said property is ${applicantsText.includes("100") ? "wholly held by the officer" : "jointly held"} 
                        and was ${data.acquired_disposed} on ${data.date_acquisition_disposed || "N/A"}.
                        ${sourceText}
                    </p>
                `;

            } else {

                /* ================= MULTIPLE PROPERTY FORMAT ================= */

                data.properties.forEach((property, index) => {

                    const propertyNumber = index + 1;

                    const ordinals = ["first", "second", "third", "fourth"];
                    const prefix = ordinals[index] ?
                        `The ${ordinals[index]} property pertains to` :
                        `The property no. ${propertyNumber} pertains to`;

                    const applicantsText = property.applicants.length > 0 ?
                        property.applicants.map(a =>
                            `${a.interest}% in the name of ${a.relationship} (${a.name})`
                        ).join(" and ") :
                        "";

                    let sourceText = "";

                    if (property.sources && property.sources.length > 0) {
                        const sourcesFormatted = property.sources.map(s =>
                            `₹${Number(s.amount).toLocaleString('en-IN')}/- paid by ${s.name}`
                        );

                        sourceText = `The funds of this property comprised of ${sourcesFormatted.join(", ")}.`;
                    }

                    propertyText += `
                        <p style="text-align:justify;">
                            <strong>${propertyNumber}.</strong>
                            ${prefix} ${property.property_description}, 
                            valued at ₹${Number(property.property_price).toLocaleString('en-IN')}/-,
                            is held by ${applicantsText}.
                            The property was ${data.acquired_disposed} on ${data.date_acquisition_disposed || "N/A"}.
                            ${sourceText}
                        </p>
                    `;
                });
            }

            /* ================= MAIN MEMO HTML ================= */

            let html = `
                    <div class="header">
                        <div><strong>F. No. PF-7625/NIC/2026-Adm.II</strong></div>
                        <div><strong>Government of India</strong></div>
                        <div><strong>Ministry of Electronics and Information Technology</strong></div>
                        <div><strong>National Informatics Centre</strong></div>
                        <div>(Administration Section-II)</div>
                    </div>

                    <div class="right">
                        A – Block, CGO Complex,<br>
                        Lodhi Road, New Delhi – 110003<br>
                        Dated: ${currentDate}
                    </div>

                    <div class="title">MEMORANDUM</div>

                    <div class="subject">
                        Subject: Intimation regarding ${data.acquired_disposed} of movable Property – reg.
                    </div>

                    <p style="text-align:justify;">
                        The undersigned is directed to refer to the application in Form-II dated 
                        ${data.date_acquisition_disposed}
                        submitted by ${data.user_details.username}, ${data.user_details.designation} 
                        (Employee Code: ${data.user_details.emp_code}),
                        regarding the ${data.acquired_disposed} of movable property.
                    </p>

                    ${propertyText}

                    <p style="text-align:justify;">
                        The intimation given in Form-II under Rule 18(3) of the Central Civil
                        Services (Conduct) Rules, 1964, is hereby acknowledged for the
                        aforementioned ${data.acquired_disposed} of movable property. The officer is advised to
                        strictly adhere to the time frame and procedure prescribed under the
                        Central Civil Services (Conduct) Rules, 1964 for such transactions in future.
                    </p>

                    <div class="signature">
                        <strong>(Balraj Singh)</strong><br>
                        HoD & Joint Director<br>
                        Tel:- 24305006<br>
                        Email:- s.balraj@nic.in
                    </div>

                    <div class="copy">
                        <p><strong>To:</strong> ${data.user_details.username}, ${data.user_details.designation}, Employee Code: ${data.user_details.emp_code}</p>
                        <p><strong>Copy to:</strong></p>
                        <ol>
                            <li>Vol-II/${data.user_details.emp_code}</li>
                            <li>Office Copy</li>
                        </ol>
                    </div>
                `;

            /* ================= EDITOR HANDLING ================= */
            document.getElementById("memoContent").innerHTML = html;
            /* ================= SHOW MODAL ================= */

            $("#memo").modal("show");
        }

        document.getElementById('memo').addEventListener('hidden.bs.modal', function() {
            if (tinymce.get("memoContent")) {
                tinymce.get("memoContent").remove();
            }
        });

        function handleBack() {
            window.history.back();
        }

        function populateEmployeeDropdown() {
            const select = document.getElementById('employee');
            if (!select) return; // important when modal not opened yet

            select.innerHTML = `<option value="">Select...</option>`;

            usersCache.forEach(user => {
                select.innerHTML += `
                    <option value="${user.uid}">
                        ${user.username ?? user.name ?? 'Unnamed'}
                    </option>
                `;
            });
        }

        function handlePrint() {
            const printSection = document.getElementById("print");
            if (!printSection) return;

            const now = new Date();
            const formattedDateTime = now.toLocaleString("en-IN", {
                day: "2-digit",
                month: "2-digit",
                year: "numeric",
                hour: "2-digit",
                minute: "2-digit",
                second: "2-digit",
            });

            const clone = printSection.cloneNode(true);
            syncValues(printSection, clone);

            const printWindow = window.open("", "_blank", "width=1000,height=700");

            printWindow.document.open();
            printWindow.document.write(`
                <html>
                <head>
                    <title>Print</title>

                    ${[...document.querySelectorAll("link, style")]
                        .map(el => el.outerHTML)
                        .join("")}

                    <style>
                        html, body {
                            height: 100%;
                        }

                        body {
                            margin: 20px;
                            padding-bottom: 70px; /* space for footer */
                        }

                        button {
                            display: none !important;
                        }

                        /* TRUE FOOTER SIMULATION */
                        .print-footer {
                            position: fixed;
                            bottom: 0;
                            left: 0;
                            right: 0;
                            height: 40px;
                            font-size: 12px;

                            
                            display: flex;
                            align-items: center;
                            justify-content: flex-end;
                            padding: 0 20px;
                            background: #fff;
                        }

                        @media print {
                            body {
                                -webkit-print-color-adjust: exact;
                            }

                            .print-footer {
                                position: fixed;
                                bottom: 0;
                            }
                        }
                    </style>
                </head>

                <body>

                    ${clone.outerHTML}

                    <div class="print-footer">
                        <strong>Printed On:</strong>&nbsp;${formattedDateTime}
                    </div>

                </body>
                </html>
            `);

            printWindow.document.close();

            setTimeout(() => {
                printWindow.focus();
                printWindow.print();
                printWindow.close();
            }, 500);
        }

        /* =========================
           SYNC INPUT VALUES
        ========================= */
        function syncValues(original, clone) {
            const originalEls = original.querySelectorAll("input, textarea, select");
            const clonedEls = clone.querySelectorAll("input, textarea, select");

            originalEls.forEach((el, i) => {
                const cloneEl = clonedEls[i];
                if (!cloneEl) return;

                if (el.tagName === "INPUT") {
                    if (el.type === "checkbox" || el.type === "radio") {
                        cloneEl.checked = el.checked;

                        if (el.checked) {
                            cloneEl.setAttribute("checked", "checked");
                        } else {
                            cloneEl.removeAttribute("checked");
                        }
                    } else {
                        cloneEl.value = el.value;
                        cloneEl.setAttribute("value", el.value);
                    }
                } else if (el.tagName === "TEXTAREA") {
                    cloneEl.value = el.value;
                    cloneEl.innerHTML = el.value;
                } else if (el.tagName === "SELECT") {
                    Array.from(el.options).forEach((opt, idx) => {
                        if (cloneEl.options[idx]) {
                            cloneEl.options[idx].selected = opt.selected;

                            if (opt.selected) {
                                cloneEl.options[idx].setAttribute("selected", "selected");
                            } else {
                                cloneEl.options[idx].removeAttribute("selected");
                            }
                        }
                    });
                }
            });
        }

        /* =========================
            FORWARD CLICK textarea + select required
         ========================= */
        async function handleforward() {
            clearValidation();

            let isValid = true;
            const formData = new FormData();
            const remarks = document.getElementById('remarks');
            const employee = document.getElementById('employee');
            const correctOM = document.getElementById('correctOM').checked ? 1 : 0;


            if (!remarks.value.trim()) {
                remarks.classList.add('is-invalid');
                isValid = false;
            }

            if (!employee.value) {
                employee.classList.add('is-invalid');
                isValid = false;
            }

            if (!isValid) return;

            formData.append("id", formId);
            formData.append("remarks", remarks.value);
            formData.append("employee", employee.value);
            formData.append('correctOM', correctOM);
            formData.append("action", "Forwarded");

            try {

                const res = await fetch("../api/updateForm.php", {
                    method: "POST",
                    body: formData
                });

                const json = await res.json();

                if (json.success) {
                    sessionStorage.setItem("alertMsg", "Forwarded successfully!");
                    showAlert("Forwarded successfully!", "success");
                    setTimeout(() => {
                        window.location.href = "inbox.php";
                    }, 3000);
                } else {
                    showAlert(json.message || "Action failed", "danger");
                }

            } catch (err) {
                showAlert("Server error", "danger");
            }
        }

        /* =========================
           BACK CLICK only textarea required
        ========================= */
        async function handleRevert() {
            clearValidation();

            const formData = new FormData();

            const remarks = document.getElementById('remarks');

            if (!remarks.value.trim()) {
                remarks.classList.add('is-invalid');
                return;
            }
            formData.append("id", formId);
            formData.append("remarks", remarks.value);
            formData.append("action", "Rejected");

            try {

                const res = await fetch("../api/updateForm.php", {
                    method: "POST",
                    body: formData
                });

                const json = await res.json();

                if (json.success) {
                    sessionStorage.setItem("alertMsg", "Reverted successfully!");
                    showAlert("Reverted successfully!", "success");
                    setTimeout(() => {
                        window.location.href = "inbox.php";
                    }, 3000);
                } else {
                    showAlert(json.message || "Action failed", "danger");
                }

            } catch (err) {
                showAlert("Server error", "danger");
            }
        }

        function clearValidation() {
            document.querySelectorAll('.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });
        }

        (async function init() {
            await LoadEmployees();
            const id = getQueryParam("id");
            if (id) {
                formId = id;
                loadmovableById(id);
            }

        })();
    </script>

</body>

</html>