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
                    Immovable Property
                </h1>
                <ol class="breadcrumb">
                    <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Home</a></li>
                    <li>New Request</li>
                    <li class="active">Immovable property</li>
                </ol>
            </section>

            <!-- Main content -->
            <section class="content">
                <form id="form1" role="form" novalidate enctype="multipart/form-data" autocomplete="off">

                    <!-- Header -->
                    <div class="text-center" style="margin-bottom:15px;">
                        <h4 class="text-uppercase"><b>Property transaction for immovable property</b></h4>
                        <small>(Prior intimation / Seeking sanction under Rule 18 (2) of the CCS (Conduct) Rules, 1964)</small>
                    </div>

                    <!-- Main Box -->
                    <div class="box box-primary">
                        <div class="box-body">

                            <div class="row">

                                <!-- Purpose -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="required-label">Purpose of application</label>
                                        <select name="purpose" class="form-control" required>
                                            <option value="">Select...</option>
                                            <option value="Sanction for transaction">Sanction for transaction</option>
                                            <option value="Prior intimation of Transaction">Prior intimation of Transaction</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Acquired / Disposed -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="required-label">Acquired or Disposed</label>
                                        <select name="acquired_disposed" class="form-control" required>
                                            <option value="">Select...</option>
                                            <option value="acquired">Acquired</option>
                                            <option value="disposed">Disposed</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Mode Acquisition -->
                                <div class="col-md-4 d-none" id="mode_acquisition">
                                    <div class="form-group">
                                        <label class="required-label fw-bold">Mode of acquisition</label>
                                        <select name="mode_acquisition" class="form-control" required>
                                            <option value="">Select...</option>
                                            <option value="Purchase">Purchase</option>
                                            <option value="Gift">Gift</option>
                                            <option value="Mortgage">Mortgage</option>
                                            <option value="Lease">Lease</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Mode Disposal -->
                                <div class="col-md-4 d-none" id="mode_disposal">
                                    <div class="form-group">
                                        <label class="required-label fw-bold">Mode of disposal</label>
                                        <select name="mode_disposal" class="form-control" required>
                                            <option value="">Select...</option>
                                            <option value="Sale">Sale</option>
                                            <option value="Gift">Gift</option>
                                            <option value="Mortgage">Mortgage</option>
                                            <option value="Lease">Lease</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Date -->
                                <div class="col-md-4 d-none" id="date_acq_dis">
                                    <div class="form-group">
                                        <label class="required-label fw-bold" id="date_acquisition_disposed">Probable date of acquisition/disposal</label>
                                        <div class="input-group date">
                                            <div class="input-group-addon">
                                                <i class="fa fa-calendar"></i>
                                            </div>
                                            <input type="text" class="form-control datepicker" name="date_acquisition_disposed" required>
                                        </div>
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

                                    <!-- Location -->
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="required-label">Full Location Details <i class="fa fa-info-circle text-primary" title="Municipal No, Street, Village, Taluka, District, State"></i></label>
                                            <input type="text" name="property_location[]" class="form-control allow-basic" required>
                                        </div>
                                    </div>

                                    <!-- Type -->
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="required-label">Description Type</label>
                                            <select name="property_description[]" class="form-control" required>
                                                <option value="">Select...</option>
                                                <option value="Housing and Other buildings">Housing and Other buildings</option>
                                                <option value="Lands">Lands</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Hold -->
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="required-label">Freehold or Leasehold</label>
                                            <select name="property_hold[]" class="form-control" required>
                                                <option value="">Select...</option>
                                                <option value="freehold">Freehold</option>
                                                <option value="leasehold">Leasehold</option>
                                            </select>
                                        </div>
                                    </div>

                                </div>
                                <!--./row-->

                                <div class="row">
                                    <!-- Price -->
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="required-label property_price_label">Sale/purchase price of the property (in Rupees) </label>
                                            <input inputmode="numeric" pattern="[0-9]*" name="property_price[]" class="form-control property_price amount-input" required>
                                            <small class="price_in_text text-success fw-bold"></small>
                                        </div>
                                    </div>

                                    <div class="col-md-8">
                                        <!-- Applicant Interest -->
                                        <label class="required-label">Applicant's interest in property (Full/Part) <i class="fa fa-info-circle  text-primary"
                                                title="Whether applicant's interest in the property is in full or part, in case of partial interest, extent of such interest must be indicated. Ownership of the property, in case transaction is not exclusively in the name of the Government servent, particulars of ownership and share of each member may be given."></i>
                                        </label>

                                        <div class="rows_applicant">
                                            <div class="row row-item-applicant">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <input type="text" name="name_applicant[]" placeholder="Name" class="form-control allow-basic" required>
                                                    </div>
                                                </div>

                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <input inputmode="numeric" pattern="[0-9]*" name="interest_applicant[]" placeholder="%" min="0" max="100" class="form-control interest_percent allow-basic" required>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <select name="relationship_applicant[]" class="form-control" required>
                                                            <option value="">Relationship...</option>
                                                            <option value="Self">Self</option>
                                                            <option value="Father">Father</option>
                                                            <option value="Mother">Mother</option>
                                                            <option value="Spouse">Spouse</option>
                                                            <option value="Brother">Brother</option>
                                                            <option value="Sister">Sister</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="col-md-2">
                                                    <button type="button" class="btn btn-success btn-sm addRow_applicant dynamic-btn">
                                                        <i class="fa fa-plus"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-danger btn-sm removeRow_applicant dynamic-btn">
                                                        <i class="fa fa-minus"></i>
                                                    </button>
                                                </div>

                                            </div>

                                        </div>
                                    </div>
                                </div>

                                <!-- Sources of Financing -->
                                <div class="d-none acquisition_sources">

                                    <label class="required-label ">Source(s) from which financed</label>

                                    <div class="rows_source">

                                        <div class="row row-item-source">

                                            <!-- Source Name -->
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <input type="text" name="source_name[]" placeholder="Source Name" class="form-control allow-basic" required>
                                                </div>
                                            </div>

                                            <!-- Amount -->
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <input type="number" name="source_amount[]" placeholder="Amount (₹)" class="form-control allow-basic source_amount" required>
                                                </div>
                                            </div>

                                            <!-- File -->
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <input type="file" name="source_document[]" class="form-control js-source-file-input">
                                                    <div class="small js-existing-source-attachment d-none"></div>
                                                </div>
                                            </div>

                                            <!-- Buttons -->
                                            <div class="col-md-2" style="margin-top:5px;">
                                                <button type="button" class="btn btn-success btn-sm addRow_source dynamic-btn">
                                                    <i class="fa fa-plus"></i>
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm removeRow_source dynamic-btn">
                                                    <i class="fa fa-minus"></i>
                                                </button>
                                            </div>

                                        </div>

                                    </div>

                                </div>

                                <!-- Disposal Property -->
                                <div class="disposal_property d-none">

                                    <label class="required-label ">Sanction/intimation Status</label>

                                    <div class="row">

                                        <!-- Status -->
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <select class="form-control disposal_status" name="disposal_property[]" required>
                                                    <option value="">Select...</option>
                                                    <option value="Yes">Yes</option>
                                                    <option value="No">No</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- File Upload -->
                                        <div class="col-md-4 disposal_file_wrapper d-none">
                                            <div class="form-group">
                                                <input type="file" class="form-control js-disposal-file-input" name="disposal_property_attachment[]">
                                                <div class="small js-existing-disposal-attachment" style="margin-top:5px; display:none;"></div>
                                            </div>
                                        </div>

                                        <!-- Reason -->
                                        <div class="col-md-5 disposal_reason_wrapper d-none">
                                            <div class="form-group">
                                                <textarea class="form-control" name="disposal_property_reason[]" placeholder="Reason for no status..."></textarea>
                                            </div>
                                        </div>

                                    </div>

                                </div>

                                <!-- Details of the Parties -->
                                <div style="margin-top:20px; padding-top:15px; border-top:1px solid #ddd;">

                                    <h4 class="text-primary"><u><b>Details of the Parties</b></u></h4>

                                    <!-- Name + Address -->
                                    <div class="row">

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="required-label">Name of Party</label>
                                                <input type="text" name="party_name[]" class="form-control allow-basic" required>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="required-label">Address of Party</label>
                                                <textarea class="form-control allow-basic party_address"
                                                    name="party_address[]" rows="2" required></textarea>
                                            </div>
                                        </div>

                                    </div>

                                    <!-- Related to Applicant -->
                                    <div class="row">

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="required-label">Related to Applicant</label>
                                                <select class="form-control party_relationship" name="party_relationship[]" required>
                                                    <option value="">Select...</option>
                                                    <option value="yes">Yes</option>
                                                    <option value="no">No</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-8 party_relationship_description  d-none">
                                            <div class="form-group">
                                                <label class="required-label">Describe relationship</label>
                                                <input type="text"
                                                    class="form-control allow-basic"
                                                    name="party_relationship_description[]"
                                                    placeholder="Describe relationship...">
                                            </div>
                                        </div>

                                    </div>

                                    <!-- Official dealing -->
                                    <div class="row">

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="required-label">Any official dealing with parties</label>
                                                <select class="form-control" name="applicant_dealing_parties[]" required>
                                                    <option value="">Select...</option>
                                                    <option value="yes">Yes</option>
                                                    <option value="no">No</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-8 applicant_dealing_parties_description d-none">
                                            <div class="form-group">
                                                <label class="required-label">Full particulars about the official dealing with parties</label>
                                                <input type="text"
                                                    class="form-control allow-basic"
                                                    name="applicant_dealing_parties_description[]"
                                                    placeholder="Full particulars about the official dealing with parties...">
                                            </div>
                                        </div>

                                    </div>

                                    <!-- Transaction Mode -->
                                    <div class="row">

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="required-label">How was transaction arranged</label>
                                                <select class="form-control" name="party_transaction_mode[]" required>
                                                    <option value="">Select...</option>
                                                    <option value="Statutory Body">Statutory Body</option>
                                                    <option value="Advertisement">Advertisement</option>
                                                    <option value="Friends and Relative">Friends and Relative</option>
                                                </select>
                                            </div>
                                        </div>

                                    </div>

                                </div>

                                <!-- Buttons -->
                                <div class="text-right">
                                    <button type="button" class="btn btn-success btn-sm addPropertyBtn">
                                        Add Property
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm removePropertyBtn">
                                        Remove
                                    </button>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="box box-primary">
                        <div class="box-body">

                            <!-- Gift + Other Info -->
                            <div class="row" style="margin-top:20px;">

                                <!-- Acquisition Gift -->
                                <div class="col-md-6 d-none" id="acquisition_gift">
                                    <div class="form-group">
                                        <label class="required-label">Sanction required under rule 13 of CCS Rules</label>
                                        <select class="form-control" name="acquisition_gift">
                                            <option value="">Select...</option>
                                            <option value="yes">Yes</option>
                                            <option value="no">No</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Other Relevant -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Any other relevant fact</label>
                                        <textarea class="form-control allow-basic" name="other_relevant" rows="2"></textarea>
                                    </div>
                                </div>

                            </div>

                            <!-- Declaration Box -->
                            <div class="box box-default" style="margin-top:20px;">
                                <div class="box-body">

                                    <!-- In Parts -->
                                    <div class="d-none" id="form1_inparts">
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" name="form1_dec" required>
                                                I, <strong class="emp_name_text underline"></strong> hereby declare that the particulars given above are true.
                                                I request permission to
                                                <span class="acquired_disposed_value text-primary"></span>
                                                the property from/to
                                                <strong class="party_name_text underline"></strong>.
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Full -->
                                    <div class="d-none" id="form1_full">
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" name="form1_dec1" required>
                                                I, <strong class="emp_name_text underline"></strong> hereby intimate the proposed
                                                <span class="acquisition_disposed_value text-primary"></span>
                                                of property by me. I declare that the particulars given above are true.
                                            </label>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="text-center">
                                <button type="button" class="btn btn-warning" onclick="saveDraft()">Save Draft</button>
                                <button type="button" class="btn btn-success" onclick="submitForm()">Submit</button>
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

    <div class="modal fade" id="confirmModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">

                <!-- Header -->
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Confirm Action</h4>
                </div>

                <!-- Body -->
                <div class="modal-body">
                    <p id="confirmMessage" style="margin-bottom:0;"></p>
                </div>

                <!-- Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-sm" id="confirmCancelBtn" data-dismiss="modal">
                        Cancel
                    </button>

                    <button type="button" class="btn btn-danger btn-sm" id="confirmOkBtn">
                        OK
                    </button>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade success-state-modal" id="successModal" tabindex="-1" role="dialog">
        <div class="modal-dialog success-state-dialog" role="document">
            <div class="modal-content success-state-content">
                <button type="button" class="success-state-close" data-dismiss="modal" aria-label="Close" onclick="window.location.href='requestLists.php'">&times;</button>
                <div class="success-state-hero">
                    <span class="success-state-check" aria-hidden="true"></span>
                </div>
                <div class="success-state-body">
                    <h2>Great!</h2>
                    <p id="successMessage" class="success-state-message">Form submitted successfully.</p>
                    <p id="successReference" class="success-state-reference"></p>
                    <a href="requestLists.php" class="success-state-action">
                        Go To Request List <span aria-hidden="true">&rarr;</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php require 'footer.php'; ?>
    <script src="../js/immovable.js"></script>

</body>

</html>