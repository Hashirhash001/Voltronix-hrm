<?php
// app/Http/Controllers/DocumentExpiryController.php

namespace App\Http\Controllers;

use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DocumentExpiryController extends Controller
{
    const ITEMS_PER_PAGE = 15;

    public function index(Request $request)
    {
        $today = Carbon::today();
        $page = $request->get('page', 1);

        $documentTypes = [
            'passport_expiry_date' => 'Passport',
            'visa_expiry_date' => 'Visa',
            'visit_expiry_date' => 'Visit Permit',
            'eid_expiry_date' => 'EID',
            'health_insurance_expiry_date' => 'Health Insurance',
            'driving_license_expiry_date' => 'Driving License',
            'iloe_insurance_expiry_date' => 'ILOE Insurance',
            'vtnx_trade_license_renewal_date' => 'VTNX Trade License',
            'po_box_renewal_date' => 'PO Box',
            'soe_card_renewal_date' => 'SOE Card',
            'dcd_card_renewal_date' => 'DCD Card',
            'voltronix_est_card_renewal_date' => 'Voltronix EST Card',
            'warehouse_ejari_renewal_date' => 'Warehouse EJARI',
            'camp_ejari_renewal_date' => 'Camp EJARI',
            'workman_insurance_expiry_date' => 'Workman Insurance',
            'etisalat_contract_expiry_date' => 'Etisalat Contract',
        ];

        // Get filters
        $statusFilter = $request->get('status', 'all');
        $documentFilter = $request->get('document', 'all');
        $searchQuery = $request->get('search', '');

        // Get all active employees
        $employees = Employee::where('status', 'active')->get();

        // Build all alerts
        $allAlerts = collect();
        foreach ($employees as $employee) {
            foreach ($documentTypes as $field => $documentName) {
                $expiryDate = $employee->getAttribute($field);

                if ($expiryDate) {
                    $daysUntilExpiry = $today->diffInDays($expiryDate, false);

                    // Determine status
                    if ($daysUntilExpiry < 0) {
                        $status = 'expired';
                        $statusLabel = 'Expired';
                        $statusClass = 'danger';
                    } elseif ($daysUntilExpiry <= 30) {
                        $status = 'critical';
                        $statusLabel = 'Critical';
                        $statusClass = 'danger';
                    } elseif ($daysUntilExpiry <= 60) {
                        $status = 'warning';
                        $statusLabel = 'Warning';
                        $statusClass = 'warning';
                    } elseif ($daysUntilExpiry <= 90) {
                        $status = 'notice';
                        $statusLabel = 'Notice';
                        $statusClass = 'info';
                    } else {
                        continue;
                    }

                    $allAlerts->push([
                        'employee' => $employee,
                        'document_name' => $documentName,
                        'document_type' => $documentName,
                        'document_field' => $field,
                        'expiry_date' => $expiryDate,
                        'days_until_expiry' => $daysUntilExpiry,
                        'status' => $status,
                        'status_label' => $statusLabel,
                        'status_class' => $statusClass,
                    ]);
                }
            }
        }

        // Calculate total status counts (from ALL alerts)
        $totalStatusCounts = [
            'expired' => $allAlerts->where('status', 'expired')->count(),
            'critical' => $allAlerts->where('status', 'critical')->count(),
            'warning' => $allAlerts->where('status', 'warning')->count(),
            'notice' => $allAlerts->where('status', 'notice')->count(),
        ];

        // Apply filters
        $filteredAlerts = $allAlerts->filter(function ($alert) use ($statusFilter, $documentFilter, $searchQuery) {
            // Apply search filter
            if ($searchQuery) {
                $searchLower = strtolower($searchQuery);
                if (!str_contains(strtolower($alert['employee']->employee_name), $searchLower) &&
                    !str_contains(strtolower($alert['employee']->staff_number), $searchLower)) {
                    return false;
                }
            }

            // Apply document filter
            if ($documentFilter !== 'all' && $alert['document_field'] !== $documentFilter) {
                return false;
            }

            // Apply status filter
            if ($statusFilter !== 'all' && $alert['status'] !== $statusFilter) {
                return false;
            }

            return true;
        })->sortBy('days_until_expiry')->values();

        // Calculate pagination
        $total = $filteredAlerts->count();
        $pages = ceil($total / self::ITEMS_PER_PAGE);
        $skip = ($page - 1) * self::ITEMS_PER_PAGE;

        // Paginate results
        $alerts = $filteredAlerts->slice($skip, self::ITEMS_PER_PAGE)->values();

        // If AJAX request, return JSON
        if ($request->ajax()) {
            return response()->json([
                'alerts' => $alerts,
                'totalStatusCounts' => $totalStatusCounts,
                'filteredCount' => $total,
                'page' => $page,
                'pages' => $pages,
                'perPage' => self::ITEMS_PER_PAGE,
            ]);
        }

        return view('document-expiry.index', compact(
            'alerts',
            'totalStatusCounts',
            'statusFilter',
            'documentFilter',
            'searchQuery',
            'documentTypes',
            'page',
            'pages',
            'total'
        ));
    }
}
