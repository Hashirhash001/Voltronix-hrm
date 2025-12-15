<?php
// app/Http/Controllers/DocumentExpiryController.php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Entity;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DocumentExpiryController extends Controller
{
    const ITEMS_PER_PAGE = 15;

    public function index(Request $request)
    {
        $today = Carbon::today();
        $page = $request->get('page', 1);

        // Define document types for each model
        $employeeDocuments = [
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

        $entityDocuments = [
            'trade_license_renewal_date' => 'Trade License',
            'est_card_renewal_date' => 'EST Card',
            'warehouse_ejari_renewal_date' => 'Warehouse EJARI',
            'camp_ejari_renewal_date' => 'Camp EJARI',
            'workman_insurance_expiry_date' => 'Workman Insurance',
        ];

        $vehicleDocuments = [
            'mulkiya_expiry_date' => 'Mulkiya (Vehicle Registration)',
            'driving_license_expiry_date' => 'Driving License',
        ];

        // Get filters
        $statusFilter = $request->get('status', 'all');
        $documentFilter = $request->get('document', 'all');
        $categoryFilter = $request->get('category', 'all');
        $searchQuery = $request->get('search', '');

        // Build all alerts
        $allAlerts = collect();

        // Add Employee document alerts
        if ($categoryFilter === 'all' || $categoryFilter === 'employee') {
            $employees = Employee::where('status', 'active')->get();
            foreach ($employees as $employee) {
                foreach ($employeeDocuments as $field => $documentName) {
                    $alert = $this->createAlert($employee, $field, $documentName, 'employee', $today);
                    if ($alert) {
                        $allAlerts->push($alert);
                    }
                }
            }
        }

        // Add Entity document alerts
        if ($categoryFilter === 'all' || $categoryFilter === 'entity') {
            $entities = Entity::where('status', 'active')->get();
            foreach ($entities as $entity) {
                foreach ($entityDocuments as $field => $documentName) {
                    $alert = $this->createAlert($entity, $field, $documentName, 'entity', $today);
                    if ($alert) {
                        $allAlerts->push($alert);
                    }
                }
            }
        }

        // Add Vehicle document alerts
        if ($categoryFilter === 'all' || $categoryFilter === 'vehicle') {
            $vehicles = Vehicle::where('status', 'active')->get();
            foreach ($vehicles as $vehicle) {
                foreach ($vehicleDocuments as $field => $documentName) {
                    $alert = $this->createAlert($vehicle, $field, $documentName, 'vehicle', $today);
                    if ($alert) {
                        $allAlerts->push($alert);
                    }
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
        $filteredAlerts = $allAlerts->filter(function ($alert) use ($statusFilter, $documentFilter, $categoryFilter, $searchQuery) {
            // Apply search filter
            if ($searchQuery) {
                $searchLower = strtolower($searchQuery);
                if (!str_contains(strtolower($alert['name']), $searchLower) &&
                    !str_contains(strtolower($alert['identifier'] ?? ''), $searchLower)) {
                    return false;
                }
            }

            // Apply document filter
            if ($documentFilter !== 'all' && $alert['document_field'] !== $documentFilter) {
                return false;
            }

            // Apply category filter
            if ($categoryFilter !== 'all' && $alert['category'] !== $categoryFilter) {
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
            'categoryFilter',
            'searchQuery',
            'employeeDocuments',
            'entityDocuments',
            'vehicleDocuments',
            'page',
            'pages',
            'total'
        ));
    }

    private function createAlert($model, $field, $documentName, $category, $today)
    {
        $expiryDate = $model->getAttribute($field);

        if (!$expiryDate) {
            return null;
        }

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
            return null; // Don't include documents expiring after 90 days
        }

        // Get appropriate name and identifier based on category
        switch ($category) {
            case 'employee':
                $name = $model->employee_name;
                $identifier = $model->staff_number;
                $viewRoute = route('employees.show', $model->id);
                break;
            case 'entity':
                $name = $model->entity_name;
                $identifier = null;
                $viewRoute = route('entities.show', $model->id);
                break;
            case 'vehicle':
                $name = $model->vehicle_name;
                $identifier = $model->vehicle_number . ' (' . $model->vehicle_plate_number . ')';
                $viewRoute = route('vehicles.show', $model->id);
                break;
            default:
                $name = 'Unknown';
                $identifier = null;
                $viewRoute = '#';
        }

        return [
            'model' => $model,
            'category' => $category,
            'category_label' => ucfirst($category),
            'name' => $name,
            'identifier' => $identifier,
            'document_name' => $documentName,
            'document_type' => $documentName,
            'document_field' => $field,
            'expiry_date' => $expiryDate,
            'days_until_expiry' => $daysUntilExpiry,
            'status' => $status,
            'status_label' => $statusLabel,
            'status_class' => $statusClass,
            'view_route' => $viewRoute,
        ];
    }
}
