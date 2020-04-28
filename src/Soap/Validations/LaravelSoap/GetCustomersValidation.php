<?php

namespace CodeDredd\Soap\Soap\Validations\LaravelSoap;

use Illuminate\Support\Facades\Validator;

class GetCustomersValidation
{
    public static function validator ($parameters = []) {
        return Validator::make($parameters, [
            '0.Request_References.*' => 'filled',
            '0.Request_References.Customer_Reference.*' => 'filled',
            '0.Request_References.Customer_Reference.ID._' => 'string',
            '0.Request_References.Customer_Reference.ID.type' => 'in:WID, Customer_ID, Customer_Reference_ID',
            '0.Request_References.Customer_Reference.Descriptor' => 'string',
            '0.Request_Criteria.*' => 'filled',
            '0.Request_Criteria.Event_Reference.*' => 'filled',
            '0.Request_Criteria.Event_Reference.ID._' => 'string',
            '0.Request_Criteria.Event_Reference.ID.type' => 'in:WID, Absence_Case_ID, Assign_Pay_Group_Event_ID, Assignable_Payroll_ID, Background_Check_ID, Background_Process_Instance_ID, Backorder_Event_ID, Budget_Amendment_ID, Change_Order_Reference_ID, Compensation_Review_Event_ID, Customer_Request_ID, Customer_Request_Reference_ID, Eligibility_Override_Event_ID, Employee_Review_ID, Employee_Severance_Worksheet_Event_ID, Goods_Delivery_Group_ID, Goods_Delivery_Run_ID, Invite_Committee_Candidate_Event_ID, IRS_1099_MISC_Adjustment_ID, Mass_Change_Requisition_Requester_ID, Medicare_Information_Event_ID, Position_Budget_Group_ID, Procurement_Mass_Close_ID, Procurement_Mass_ReOpen_ID, Procurement_Roll_Forward_ID, Quick_Issue_Reference_ID, Receipt_Number, Request_Leave_of_Absence_ID, Requisition_Sourcing_Event_ID, Spend_Authorization_ID, Spend_Authorization_Mass_Close_ID, Student_Dismissal_Event_ID, Student_Employment_Eligibility_Event_ID, Student_Hold_Assignment_Override_Event_ID, Student_Institutional_Withdrawal_Event_ID, Student_Leave_of_Absence_Event_ID, Supplier_Invoice_Contract_ID, Workday_Project_ID, Worker_Time_Card_ID',
            '0.Request_Criteria.Event_Reference.Descriptor' => 'string',
            '0.Request_Criteria.Reporting_Transaction_Reference.*' => 'filled',
            '0.Request_Criteria.Reporting_Transaction_Reference.ID._' => 'string',
            '0.Request_Criteria.Reporting_Transaction_Reference.ID.type' => 'in:WID, Ad_hoc_Payment_Reference_ID, Advanced_Ship_Notice_ID, Advanced_Ship_Notice_Number, Alternate_Supplier_Contract_ID, Award_Contract_Amendment_ID, Award_Proposal_ID, Award_Reference_ID, Bank_Account_Transfer_Payment_ID, Cash_Sale_ID, Change_Order_Reference_ID, Consolidated_Invoice_ID, Customer_Contract_Alternate_Reference_ID, Customer_Contract_Amendment_Reference_ID, Customer_Contract_Intercompany_ID, Customer_Contract_Reference_ID, Customer_Invoice_Adjustment_Reference_ID, Customer_Invoice_Reference_ID, Customer_Overpayment_Reference_ID, Customer_Payment_for_Invoices_Reference_ID, Customer_Refund_Reference_ID, Document_Number, EFT_Payment_ID, Expense_Report_Reference_ID, Good_Delivery_ID, Goods_Delivery_Run_ID, Historical_Student_Charge_ID, Internal_Service_Delivery_Document_Number, Internal_Service_Delivery_ID, Inventory_Count_Sheet_Reference_ID, Inventory_Par_Count_Reference_ID, Inventory_Pick_List_Reference_ID, Inventory_Return_Reference_ID, Inventory_Ship_List_Reference_ID, Inventory_Stock_Request_Reference_ID, Miscellaneous_Payment_Request_Reference_ID, PO_Acknowledgement_Number, PO_Acknowledgement_Reference_ID, Procurement_Card_Transaction_Verification_ID, Purchase_Order_Reference_ID, Quick_Issue_Reference_ID, Receipt_Number, Request_for_Quote_Award_ID, Request_for_Quote_ID, Request_for_Quote_Response_ID, Requisition_Number, Requisition_Template_ID, Return_to_Supplier_ID, Sales_Order_Reference_ID, Spend_Authorization_ID, Student_Application_Fee_Payment_ID, Student_Charge_Adjustment_ID, Student_Charge_Document_ID, Student_Charge_ID, Student_Credit_Memo_ID, Student_Disbursement_Payment_ID, Student_Historical_Payment_ID, Student_Payment_ID, Student_Sponsor_Contract_ID, Student_Sponsor_Payment_ID, Student_Sponsor_Refund_Payment_ID, Student_Waiver_Payment_ID, Supplier_Acknowledgement_Number, Supplier_Contract_Amendment_ID, Supplier_Contract_History_ID, Supplier_Contract_ID, Supplier_Invoice_Adjustment_Reference_ID, Supplier_Invoice_Contract_ID, Supplier_Invoice_Reference_ID, Supplier_Invoice_Request_Document_Number, Supplier_Invoice_Request_ID',
            '0.Request_Criteria.Reporting_Transaction_Reference.ID.parent_id' => 'string',
            '0.Request_Criteria.Reporting_Transaction_Reference.ID.parent_type' => 'string',
            '0.Request_Criteria.Reporting_Transaction_Reference.Descriptor' => 'string',
            '0.Request_Criteria.Customer_ID' => 'string|nullable',
            '0.Request_Criteria.Customer_Name' => 'string|nullable',
            '0.Request_Criteria.Customer_Category_Reference.*' => 'filled',
            '0.Request_Criteria.Customer_Category_Reference.ID._' => 'string',
            '0.Request_Criteria.Customer_Category_Reference.ID.type' => 'in:WID, Customer_Category_ID',
            '0.Request_Criteria.Customer_Category_Reference.Descriptor' => 'string',
            '0.Request_Criteria.Customer_Group_Reference.*' => 'filled',
            '0.Request_Criteria.Customer_Group_Reference.ID._' => 'string',
            '0.Request_Criteria.Customer_Group_Reference.ID.type' => 'in:WID, Customer_Group_ID',
            '0.Request_Criteria.Customer_Group_Reference.Descriptor' => 'string',
            '0.Request_Criteria.Payment_Terms_Reference.*' => 'filled',
            '0.Request_Criteria.Payment_Terms_Reference.ID._' => 'string',
            '0.Request_Criteria.Payment_Terms_Reference.ID.type' => 'in:WID, Payment_Terms_ID',
            '0.Request_Criteria.Payment_Terms_Reference.Descriptor' => 'string',
            '0.Request_Criteria.Default_Payment_Type_Reference.*' => 'filled',
            '0.Request_Criteria.Default_Payment_Type_Reference.ID._' => 'string',
            '0.Request_Criteria.Default_Payment_Type_Reference.ID.type' => 'in:WID, Payment_Type_ID',
            '0.Request_Criteria.Default_Payment_Type_Reference.Descriptor' => 'string',
            '0.Request_Criteria.Customer_Status_Value_Reference.*' => 'filled',
            '0.Request_Criteria.Customer_Status_Value_Reference.ID._' => 'string',
            '0.Request_Criteria.Customer_Status_Value_Reference.ID.type' => 'in:WID, Business_Entity_Status_Value_ID',
            '0.Request_Criteria.Customer_Status_Value_Reference.Descriptor' => 'string',
            '0.Request_Criteria.Reason_for_Customer_Status_Change_Reference.*' => 'filled',
            '0.Request_Criteria.Reason_for_Customer_Status_Change_Reference.ID._' => 'string',
            '0.Request_Criteria.Reason_for_Customer_Status_Change_Reference.ID.type' => 'in:WID, Reason_for_Customer_Status_Change_ID',
            '0.Request_Criteria.Reason_for_Customer_Status_Change_Reference.Descriptor' => 'string',
            '0.Request_Criteria.Include_Basic_Worktag' => 'boolean|nullable',
            '0.Response_Filter.As_Of_Effective_Date' => 'date|nullable',
            '0.Response_Filter.As_Of_Entry_DateTime' => 'string|nullable',
            '0.Response_Filter.Page' => 'integer|nullable',
            '0.Response_Filter.Count' => 'integer|nullable',
            '0.Response_Group.Include_Reference' => 'boolean|nullable',
            '0.Response_Group.Include_Customer_Data' => 'boolean|nullable',
            '0.Response_Group.Include_Customer_Balance' => 'boolean|nullable',
            '0.Response_Group.Include_Customer_Activity_Detail' => 'boolean|nullable',
            '0.version' => 'string',
        ]);
    }
}
