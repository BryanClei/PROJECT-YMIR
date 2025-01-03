<?php

namespace App\Functions;

use App\Models\JobOrder;
use App\Response\Message;
use App\Models\JOPOTransaction;
use App\Models\JobOrderTransaction;
use Illuminate\Support\Facades\Log;
use App\Models\JobOrderPurchaseOrder;

class GlobalFunction
{
    // SUCCESS
    public static function save($message, $result = [])
    {
        return response()->json(
            [
                "message" => $message,
                "result" => $result,
            ],
            Message::CREATED_STATUS
        );
    }
    public static function responseFunction($message, $result = [])
    {
        return response()->json(
            [
                "message" => $message,
                "result" => $result,
            ],
            Message::SUCESS_STATUS
        );
    }
    public static function notFound($message)
    {
        return response()->json(
            [
                "message" => $message,
            ],
            Message::DATA_NOT_FOUND
        );
    }

    public static function invalid($message)
    {
        return response()->json(
            [
                "message" => $message,
            ],
            Message::UNPROCESS_STATUS
        );
    }

    public static function denied($message, $result = [])
    {
        return response()->json(
            [
                "message" => $message,
                "result" => $result,
            ],
            Message::DENIED_STATUS
        );
    }

    public static function stored($message, $result = [])
    {
        return response()->json(
            [
                "message" => $message,
                "result" => $result,
            ],
            Message::CREATED_STATUS
        );
    }

    public static function uploadSuccessful($message, $uploadedFiles)
    {
        return response()->json(
            [
                "message" => $message,
                "uploaded_files" => $uploadedFiles,
            ],
            Message::SUCESS_STATUS
        );
    }

    public static function uploadfailed($message, $filename)
    {
        return response()->json(
            [
                "error" => $message,
                "filename" => $filename,
            ],
            Message::UNPROCESS_STATUS
        );
    }

    public static function displayfile($message, $result = [])
    {
        return response()->json(
            [
                "message" => $message,
                "result" => $result,
            ],
            Message::SUCESS_STATUS
        );
    }

    public static function badRequest($message)
    {
        return response()->json(["error" => $message], Message::BAD_REQUEST);
    }

    public static function job_request_requestor_setting_id(
        $requestor_company_id,
        $requestor_business_id,
        $requestor_deptartment_id,
        $requestor_department_unit_id,
        $requestor_sub_unit_id,
        $requestor_location_id
    ) {
        return JobOrder::where("company_id", $requestor_company_id)
            ->where("business_unit_id", $requestor_business_id)
            ->where("department_id", $requestor_deptartment_id)
            ->where("department_unit_id", $requestor_department_unit_id)
            ->where("sub_unit_id", $requestor_sub_unit_id)
            ->where("location_id", $requestor_location_id)
            ->first();
    }

    public static function job_request_charger_setting_id(
        $company_id,
        $business_unit_id,
        $department_id,
        $department_unit_id,
        $sub_unit_id,
        $location_id
    ) {
        return JobOrder::where("company_id", $company_id)
            ->where("business_unit_id", $business_unit_id)
            ->where("department_id", $department_id)
            ->where("department_unit_id", $department_unit_id)
            ->where("sub_unit_id", $sub_unit_id)
            ->where("location_id", $location_id)
            ->first();
    }

    public static function job_request_purchase_order_requestor_setting_id(
        $requestor_company_id,
        $requestor_business_id,
        $requestor_deptartment_id
    ) {
        return JobOrderPurchaseOrder::where("company_id", $requestor_company_id)
            ->where("business_unit_id", $requestor_business_id)
            ->where("department_id", $requestor_deptartment_id)
            ->first();
    }

    public static function job_request_purchase_order_charger_setting_id(
        $company_id,
        $business_unit_id,
        $department_id
    ) {
        return JobOrderPurchaseOrder::where("company_id", $company_id)
            ->where("business_unit_id", $business_unit_id)
            ->where("department_id", $department_id)

            ->first();
    }

    public static function latest_jr($current_year)
    {
        return JobOrderTransaction::withTrashed()
            ->where("jo_year_number_id", "like", $current_year . "-JR-%")
            ->orderByRaw(
                "CAST(SUBSTRING_INDEX(jo_year_number_id, '-', -1) AS UNSIGNED) DESC"
            )
            ->first();
    }

    public static function latest_jo($current_year)
    {
        return JOPOTransaction::withTrashed()
            ->where("po_year_number_id", "like", $current_year . "-JO-%")
            ->orderByRaw(
                "CAST(SUBSTRING_INDEX(po_year_number_id, '-', -1) AS UNSIGNED) DESC"
            )
            ->first();
    }

    public static function error(\Exception $e, array $contextData = [])
    {
        // Get the specific error details
        $errorContext = [
            "error_type" => get_class($e),
            "error_code" => $e->getCode(),
            "error_message" => $e->getMessage(),
            "file" => $e->getFile(),
            "line" => $e->getLine(),
            "context_data" => $contextData,
            "timestamp" => now()->format("Y-m-d H:i:s"),
        ];

        // Log the detailed error
        Log::error("Operation Failed", $errorContext);

        // Generate unique error reference
        $errorReference = uniqid("ERR_");

        return response()->json(
            [
                "success" => false,
                "message" => self::getDetailedErrorMessage($e),
                "error_details" => config("app.debug") ? $errorContext : null,
                "error_reference" => $errorReference,
            ],
            500
        );
    }

    /**
     * Get detailed error message based on exception type
     *
     * @param \Exception $e
     * @return string
     */
    private static function getDetailedErrorMessage(\Exception $e): string
    {
        // Handle specific database errors
        if (
            $e instanceof \PDOException ||
            $e instanceof \Illuminate\Database\QueryException
        ) {
            if (stripos($e->getMessage(), "duplicate entry") !== false) {
                return "A duplicate record was found. Please check your input data.";
            }
            if (
                stripos($e->getMessage(), "foreign key constraint fails") !==
                false
            ) {
                return "Referenced data not found. Please ensure all related data exists.";
            }
            return "A database error occurred while processing your request.";
        }

        // Handle validation errors
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            return "Invalid input data provided. Please check your form data.";
        }

        // Handle file system errors
        if ($e instanceof \Illuminate\Filesystem\FileNotFoundException) {
            return "Required file not found. Please check file attachments.";
        }

        // Handle authentication/authorization errors
        if ($e instanceof \Illuminate\Auth\AuthenticationException) {
            return "Authentication failed. Please login again.";
        }
        if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return "You do not have permission to perform this action.";
        }

        // Handle custom exceptions
        if ($e instanceof \App\Exceptions\CustomException) {
            return $e->getMessage();
        }

        // For any other unknown errors
        return "An unexpected error occurred while processing your request.";
    }

    /**
     * Create custom error message for specific modules
     *
     * @param string $module
     * @param \Exception $e
     * @return string
     */
    public static function getModuleErrorMessage(
        string $module,
        \Exception $e
    ): string {
        $baseMessage = self::getDetailedErrorMessage($e);

        $moduleSpecificMessages = [
            "job_order" => [
                "duplicate" => "A duplicate job order was found.",
                "not_found" => "Job order reference not found.",
                "invalid_status" => "Invalid job order status transition.",
            ],
            "purchase_order" => [
                "duplicate" => "A duplicate purchase order was found.",
                "not_found" => "Purchase order reference not found.",
                "invalid_status" => "Invalid purchase order status transition.",
            ],
            // Add more modules as needed
        ];

        // Check if there's a specific message for this module and error type
        if (isset($moduleSpecificMessages[$module])) {
            foreach (
                $moduleSpecificMessages[$module]
                as $errorType => $message
            ) {
                if (stripos($e->getMessage(), $errorType) !== false) {
                    return $message;
                }
            }
        }

        return $baseMessage;
    }
}
