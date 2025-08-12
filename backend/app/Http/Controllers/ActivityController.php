<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::query();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $activities = $query->orderByDesc('id')->get();

        $activeCount = Activity::where('status', 'active')->count();
        $inactiveCount = Activity::where('status', 'inactive')->count();

        return response()->json([
            'activities' => $activities->map(function (Activity $a) {
                return [
                    'id' => $a->id,
                    'name' => $a->name,
                    'description' => $a->description,
                    'price' => $a->price,
                    'status' => $a->status,
                    'child_price' => $a->child_price,
                    'commission_structure' => [
                        'agent_commission' => $a->adult_agent_commission,
                        'staff_commission' => $a->adult_staff_commission,
                        'boat_boy_commission' => $a->adult_boat_boy_commission,
                    ],
                    'total_commission' => $a->adult_total_commission,
                    'admin_share' => $a->adult_admin_share,
                    'child_commission_structure' => [
                        'agent_commission' => $a->child_agent_commission,
                        'staff_commission' => $a->child_staff_commission,
                        'boat_boy_commission' => $a->child_boat_boy_commission,
                    ],
                    'child_total_commission' => $a->child_total_commission,
                    'child_admin_share' => $a->child_admin_share,
                ];
            }),
            'total' => $activities->count(),
            'active_count' => $activeCount,
            'inactive_count' => $inactiveCount,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
            'adult_commission_structure.agent_commission' => ['required', 'integer'],
            'adult_commission_structure.staff_commission' => ['required', 'integer'],
            'adult_commission_structure.boat_boy_commission' => ['required', 'integer'],
            'child_price' => ['nullable', 'integer', 'min:0'],
            'child_commission_structure.agent_commission' => ['nullable', 'integer'],
            'child_commission_structure.staff_commission' => ['nullable', 'integer'],
            'child_commission_structure.boat_boy_commission' => ['nullable', 'integer'],
            'gst_rate' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $agent = $data['adult_commission_structure']['agent_commission'];
        $staff = $data['adult_commission_structure']['staff_commission'];
        $boat = $data['adult_commission_structure']['boat_boy_commission'];
        $totalCommission = $agent + $staff + $boat;
        if ($totalCommission > $data['price']) {
            return response()->json(['message' => 'Total commission cannot exceed price'], 422);
        }

        // Child values (optional)
        $childPrice = $data['child_price'] ?? null;
        $childAgent = data_get($data, 'child_commission_structure.agent_commission', 0);
        $childStaff = data_get($data, 'child_commission_structure.staff_commission', 0);
        $childBoat = data_get($data, 'child_commission_structure.boat_boy_commission', 0);
        $childTotal = ($childPrice !== null) ? ($childAgent + $childStaff + $childBoat) : 0;
        $childAdmin = ($childPrice !== null) ? max(0, $childPrice - $childTotal) : 0;

        $activity = Activity::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'status' => $data['status'],
            'adult_agent_commission' => $agent,
            'adult_staff_commission' => $staff,
            'adult_boat_boy_commission' => $boat,
            'adult_total_commission' => $totalCommission,
            'adult_admin_share' => $data['price'] - $totalCommission,
            'child_agent_commission' => $childAgent,
            'child_staff_commission' => $childStaff,
            'child_boat_boy_commission' => $childBoat,
            'child_total_commission' => $childTotal,
            'child_admin_share' => $childAdmin,
            'gst_rate' => $data['gst_rate'] ?? 18,
        ]);

        return response()->json($activity, 201);
    }

    public function show(Activity $activity)
    {
        return response()->json($activity);
    }

    public function update(Request $request, Activity $activity)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'price' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'in:active,inactive'],
            'adult_commission_structure.agent_commission' => ['sometimes', 'integer', 'min:0'],
            'adult_commission_structure.staff_commission' => ['sometimes', 'integer', 'min:0'],
            'adult_commission_structure.boat_boy_commission' => ['sometimes', 'integer', 'min:0'],
            'gst_rate' => ['sometimes', 'integer', 'min:0', 'max:100'],
        ]);

        $price = $data['price'] ?? $activity->price;
        $agent = $data['adult_commission_structure']['agent_commission'] ?? $activity->adult_agent_commission;
        $staff = $data['adult_commission_structure']['staff_commission'] ?? $activity->adult_staff_commission;
        $boat = $data['adult_commission_structure']['boat_boy_commission'] ?? $activity->adult_boat_boy_commission;
        $totalCommission = $agent + $staff + $boat;
        if ($totalCommission > $price) {
            return response()->json(['message' => 'Total commission cannot exceed price'], 422);
        }

        $childPrice = $data['child_price'] ?? null;
        $childAgent = data_get($data, 'child_commission_structure.agent_commission', 0);
        $childStaff = data_get($data, 'child_commission_structure.staff_commission', 0);
        $childBoat = data_get($data, 'child_commission_structure.boat_boy_commission', 0);
        $childTotal = ($childPrice !== null) ? ($childAgent + $childStaff + $childBoat) : 0;
        $childAdmin = ($childPrice !== null) ? max(0, $childPrice - $childTotal) : 0;

        $activity->update([
            'name' => $data['name'] ?? $activity->name,
            'description' => $data['description'] ?? $activity->description,
            'price' => $price,
            'status' => $data['status'] ?? $activity->status,
            'adult_agent_commission' => $agent,
            'adult_staff_commission' => $staff,
            'adult_boat_boy_commission' => $boat,
            'adult_total_commission' => $totalCommission,
            'adult_admin_share' => $price - $totalCommission,
            'child_agent_commission' => $childAgent,
            'child_staff_commission' => $childStaff,
            'child_boat_boy_commission' => $childBoat,
            'child_total_commission' => $childTotal,
            'child_admin_share' => $childAdmin,
            'gst_rate' => $data['gst_rate'] ?? $activity->gst_rate,
        ]);

        return response()->json($activity);
    }

    public function destroy(Activity $activity)
    {
        $activity->delete();
        return response()->json(['success' => true]);
    }

    public function toggle(Activity $activity)
    {
        $activity->status = $activity->status === 'active' ? 'inactive' : 'active';
        $activity->save();
        return response()->json($activity);
    }
}


