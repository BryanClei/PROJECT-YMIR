<?php
namespace App\Response;

class Message
{
    //STATUS CODES
    const CREATED_STATUS = 201;
    const UNPROCESS_STATUS = 422;
    const DATA_NOT_FOUND = 404;
    const SUCESS_STATUS = 200;
    const DENIED_STATUS = 403;
    const BAD_REQUEST = 400;

    //CRUD OPERATION
    const REGISTERED = "User successfully save.";
    const ROLE_SAVE = "Role successfully save.";
    const COMPANY_SAVE = "Company successfully save.";
    const BUSINESS_SAVE = "Business unit successfully save.";
    const DEPARTMENT_SAVE = "Department successfully save.";
    const SUB_UNIT_SAVE = "Sub unit successfully save.";
    const LOCATION_SAVE = "Location successfully save.";
    const WAREHOUSE_SAVE = "Warehouse successfully save.";
    const ACCOUNT_TYPE_SAVE = "Account type successfully save.";
    const ACCOUNT_GROUP_SAVE = "Account group successfully save.";
    const ACCOUNT_SUB_GROUP_SAVE = "Account sub-group  successfully save.";
    const FINANCIAL_SAVE = "Financial statement successfully save.";
    const NORMAL_BALANCE_SAVE = "Normal balance successfully save.";
    const ACCOUNT_TITLE_UNIT_SAVE = "Account title unit successfully save.";
    const TYPE_SAVE = "Type successfully save.";
    const UOM_SAVE = "Unit of measurements successfully save.";
    const SUPPLIER_SAVE = "Supplier successfully save.";
    const UNIT_SAVE = "Unit successfully save.";
    const ITEM_SAVE = "Item successfully save.";
    const DEPARTMENT_UNIT_SAVE = "Department unit successfully save.";
    const ACCOUNT_TITLE_SAVE = "Account title successfully save.";
    const PURCHASE_REQUEST_SAVE = "Purchase request successfully save.";
    const APPROVERS_SAVE = "Approvers successfully save.";
    const PURCHASE_ORDER_SAVE = "Purchase order successfully save.";
    const CATEGORIES_SAVE = "Category successfully save.";
    const UPLOAD_SUCCESSFUL = "Upload successfully save.";
    const NO_FILE_UPLOAD = "PR successfully created. No file uploaded.";
    const ASSET_SAVE = "Asset save successfully.";
    const RR_SAVE = "Received Receipt successfully save.";
    const ALLOWABLE_SAVE = "Allowable percentage successfully save.";
    const SMALL_TOOLS_SAVE = "Small tools save successfully.";
    const MIN_MAX_SET = "Job order min and max successfully set.";
    const MIN_SET = "Job order min successfully set.";
    const CREDIT_SAVE = "Credit successfully created.";
    const PURCHASE_REQUEST_DRAFT_SAVE = "Draft successfully save.";
    const CALENDAR_SETUP_SAVE = "Calendar setup successfully save.";
    const CHARGING_SAVE = "One Charging successfully save.";
    const SHIP_TO_SAVE = "Ship to successfully save.";

    // DISPLAY DATA
    const USER_DISPLAY = "User display successfully.";
    const ROLE_DISPLAY = "Role display successfully.";
    const COMPANY_DISPLAY = "Company display successfully.";
    const BUSINESS_DISPLAY = "Business unit display successfully.";
    const DEPARTMENT_DISPLAY = "Department display successfully.";
    const SUB_UNIT_DISPLAY = "Sub unit display successfully.";
    const LOCATION_DISPLAY = "Location display successfully.";
    const WAREHOUSE_DISPLAY = "Warehouse display successfully.";
    const ACCOUNT_TYPE_DISPLAY = "Account type display successfully.";
    const ACCOUNT_GROUP_DISPLAY = "Account group display successfully.";
    const ACCOUNT_SUB_GROUP_DISPLAY = "Account sub-group display successfully.";
    const FINANCIAL_DISPLAY = "Financial statement display successfully.";
    const NORMAL_BALANCE_DISPLAY = "Normal balance display successfully.";
    const ACCOUNT_TITLE_UNIT_DISPLAY = "Account title unit display successfully.";
    const TYPE_DISPLAY = "Type display successfully.";
    const UOM_DISPLAY = "Unit of measurements display successfully.";
    const SUPPLIER_DISPLAY = "Supplier display successfully.";
    const UNIT_DISPLAY = "Unit display successfully.";
    const ITEM_DISPLAY = "Item display successfully.";
    const ITEM_PREVIOUS_PRICE = "Previous item display";
    const DEPARTMENT_UNIT_DISPLAY = "Department unit  display successfully.";
    const ACCOUNT_TITLE_DISPLAY = "Account title  display successfully.";
    const PURCHASE_REQUEST_DISPLAY = "Purchase request  display successfully.";
    const PURCHASE_REQUEST_PLACE_ORDER = "Purchase request has been placed successfully.";
    const PURCHASE_ORDER_PLACE_ORDER = "Purchase request has been placed successfully.";
    const APPROVERS_DISPLAY = "Approvers display successfully.";
    const PURCHASE_ORDER_DISPLAY = "Purchase order  display successfully.";
    const CATEGORIES_DISPLAY = "Category display successfully.";
    const FILE_DISPLAY = "File display successfully.";
    const ASSET_DISPLAY = "Assets display succeccfully.";
    const RR_DISPLAY = "Received Receipt display successfully.";
    const DISPLAY_COUNT = "Count display successfully.";
    const DISPLAY_LOG_HISTORY = "Log history display successfully.";
    const DISPLAY_ALLOWABLE = "Allowable percentage display successfully.";
    const SMALL_TOOLS_DISPLAY = "Small tools display successfully.";
    const MIN_MAX_DISPLAY = "Job order min and max display successfully.";
    const CREDIT_DISPLAY = "Credit display successfully.";
    const PURCHASE_REQUEST_DRAFT_DISPLAY = "Draft display successfully.";
    const CALENDAR_SETUP = "Calendar setup display successfully.";
    const CHARGING_DISPLAY = "One Charging display successfully.";
    const SHIP_TO_DISPLAY = "Ship display successfully.";

    //UPDATE
    const USER_UPDATE = "User successfully updated.";
    const ROLE_UPDATE = "Role successfully updated.";
    const COMPANY_UPDATE = "Company successfully updated.";
    const BUSINESS_UPDATE = "Business unit successfully updated.";
    const DEPARTMENT_UPDATE = "Department successfully updated.";
    const SUB_UNIT_UPDATE = "Sub unit successfully updated.";
    const LOCATION_UPDATE = "Location successfully updated.";
    const WAREHOUSE_UPDATE = "Warehouse successfully updated.";
    const ACCOUNT_TYPE_UPDATE = "Account type successfully updated.";
    const ACCOUNT_GROUP_UPDATE = "Account group successfully updated.";
    const ACCOUNT_SUB_GROUP_UPDATE = "Account sub-group successfully updated.";
    const FINANCIAL_UPDATE = "Financial statement successfully updated.";
    const NORMAL_BALANCE_UPDATE = "Normal balance successfully updated.";
    const ACCOUNT_TITLE_UNIT_UPDATE = "Account title unit successfully updated.";
    const TYPE_UPDATE = "Type successfully updated.";
    const UOM_UPDATE = "Unit of measurements successfully updated.";
    const SUPPLIER_UPDATE = "Supplier successfully updated.";
    const UNIT_UPDATE = "Unit successfully updated.";
    const ITEM_UPDATE = "Item successfully updated.";
    const DEPARTMENT_UNIT_UPDATE = "Department unit  successfully updated.";
    const ACCOUNT_TITLE_UPDATE = "Account title successfully updated.";
    const PURCHASE_REQUEST_UPDATE = "Purchase request successfully updated.";
    const APPROVERS_UPDATE = "Approvers successfully updated.";
    const PURCHASE_ORDER_UPDATE = "Purchase order successfully updated.";
    const PURCHASE_ORDER_RETURN = "Purchase order successfully return.";
    const CATEGORIES_UPDATE = "Category successfully updated.";
    const ASSET_UPDATE = "Asset successfully update.";
    const RESUBMITTED = "Purchase request successfully resubmitted.";
    const RESUBMITTED_PO = "Purchase order successfully resubmitted.";
    const BUYER_TAGGED = "Buyer tagged successfully.";
    const BUYER_UPDATED = "Buyer updated succesfully.";
    const CANCELLED_ALREADY = "Purchase Request already cancelled. Please Contact the requestor for more details";
    const PO_CANCELLED_ALREADY = "Purchase Order already cancelled by requestor. Please Contact the requestor for more details";
    const VOIDED_ALREADY = "Purchase Request already voided by requestor. Please Contact the requestor for more details";
    const RR_UPDATE = "RR transaction updated successfully";
    const RR_CANCELLATION = "RR transaction cancelled successfully";
    const ALLOWABLE_UPDATE = "Allowable percentage update successfully.";
    const REMARKS_UPDATE = "Remarks updated successfully.";
    const LOG_SUCCESSFULLY = "Log successfully.";
    const SMALL_TOOLS_UPDATE = "Small tools update successfully.";
    const MIN_MAX_UPDATE = "Job order min and max update successfully.";
    const PURCHASE_REQUEST_AND_ORDER_UPDATE = "Purchase request and purchase order updated successfully.";
    const CREDIT_UPDATE = "Credit updated successfully.";
    const PURCHASE_REQUEST_DRAFT_UPDATE = "Purchase request draft update successfully.";
    const CALENDAR_SETUP_UPDATED = "Calendar setup updated successfully.";
    const SHIP_TO_UPDATED = "Ship updated successfully.";

    const RR_TAGGED_FISTO = "RR orders tagged successfully.";
    const RR_ETD_ITEMS = "RR orders sync successfully.";

    const SYNC_ORDERS = "Orders sync successfully.";

    //SOFT DELETE
    const ARCHIVE_STATUS = "Successfully archived.";
    const RESTORE_STATUS = "Successfully restored.";
    //ACCOUNT RESPONSE
    const INVALID_RESPONSE = "The provided credentials are incorrect.";
    const CHANGE_PASSWORD = "Password successfully changed.";
    const LOGIN_USER = "Log-in successfully.";
    const LOGOUT_USER = "Log-out successfully.";

    // DISPLAY ERRORS
    const NOT_FOUND = "Data not found.";
    const FILE_NOT_FOUND = "File not found.";
    const SMALL_TOOLS_IN_USE = "The Small Tools is in use. Cannot be deleted.";
    const ITEM_NOT_FOUND = "Item does not exist.";
    const MIN_ERROR = "Only one record is allowed.";
    const NO_ITEM_FOUND = "No item found to tag.";
    const NO_MIN_MAX = "Invalid setup please contact support.";
    const NO_DATA_FOUND = "No data found.";
    //VALIDATION
    const SINGLE_VALIDATION = "Data has been validated.";
    const INVALID_ACTION = "Invalid action.";
    const NEW_PASSWORD = "Please change your password.";
    const EXISTS = "Data already exists.";
    const ACCESS_DENIED = "You do not have permission.";
    const IN_USE_COMPANY = "This company is in used.";
    const IN_USE_DEPARTMENT = "This department is in used.";
    const IN_USE_DEPARTMENT_UNIT = "This department unit is in used.";
    const IN_USE_SUB_UNIT = "This sub unit is in used.";
    const IN_USE_BUSINESS_UNIT = "This business unit is in used.";
    const QUANTITY_VALIDATION = "The received item cannot be more than the quantity.";
    const MAX_QUANTITY_VALIDATION = "The received item cannot be more than the allowable quantity.";
    const ALREADY_HAVE_PO = "This purchase request cannot be cancelled because it has active purchase order.";
    const ALREADY_HAVE_RR = "This purchase order cannot be cancelled because it has been received.";

    const NO_APPROVERS = "No approvers yet.";
    const NO_APPROVERS_SETTINGS_YET = "No approvers settings yet.";
    const NO_APPROVERS_PRICE = "No assigned approvers for this price range";

    //PR RESPONSE
    const CANCELLED = "Purchase request has been cancelled.";
    const REJECTED = "Purchase request has been rejected.";
    const VOIDED = "Purchase request has been voided.";
    const APPORVED = "Purchase request successfully approved.";
    const APPROVED_PO = "Purchase order successfully approved.";

    //PO RESPONSE
    const PO_CANCELLED = "Purchase order has been cancelled.";
    const PO_REJECTED = "Purchase order has been rejected.";
    const PO_VOIDED = "Purchase order has been voided.";
    const PO_APPORVED = "Purchase order successfully approved.";

    //JR Response
    const JOB_REQUEST_SAVE = "Job request successfully save.";
    const JOB_REQUEST_CANCELLED = "Job request has been cancelled.";
    const JOB_REQUEST_UPDATE = "Job request successfully updated  .";

    //Jo Response
    const JOB_ORDER_SAVE = "Job order successfully save.";
    const JO_CANCELLED = "Job order has been rejected.";

    const JR_LAYER_APPROVER_VALIDATION = "You're not the current approver for this jr transaction.";
    const JO_LAYER_APPROVER_VALIDATION = "You're not the current approver for this jo transaction.";

    const PR_LAYER_APPROVER_VALIDATION = "You're not the current approver for this pr transaction.";
    const PO_LAYER_APPROVER_VALIDATION = "You're not the current approver for this po transaction.";

    //Jr Jo Response
    const PURCHASE_REQUEST_AND_ORDER_SAVE = "Job Request and Job Order save successfully.";
    const JR_AND_JO_CANCELLED_REMAINING_ITEMS = "Job Request and Job Order have been cancelled for the remaining items.";
    const JOB_REQUEST_AND_ORDER_UPDATE = "Job request and job order successfully updated.";
    const JR_AND_JO_CANCELLED = "Job Request and Job Order have been cancelled.";

    const VOIDED_FISTO_RR = "";
}
