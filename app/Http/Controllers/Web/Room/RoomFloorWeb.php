<?php
namespace App\Http\Controllers\Web\Room;

use App\Http\Controllers\Controller;
use App\Http\Resources\Room\RoomResource;
use App\Models\Room;
use Inertia\Inertia;

class RoomFloorWeb extends Controller
{
    public function view($id){
        $room = Room::with([
            'floor.subBranch.branch',
            'floor.subBranch.timeSettings',
            'floor.subBranch.checkinSettings', 
            'floor.subBranch.penaltySettings',
            'floor.subBranch.taxSettings',
            'roomType',
            'roomType.pricingRanges' => function($query) use ($id) {
                $room = Room::find($id);
                $subBranchId = $room?->floor?->sub_branch_id;
                
                $query->where('sub_branch_id', $subBranchId)
                    ->active()
                    ->effectiveNow()
                    ->with('rateType')
                    ->orderByRaw('time_from_minutes NULLS LAST');
            },
            'currentBooking.rateType',
            'currentBooking.customer',
            'currentBooking.bookingConsumptions.product',
            'statusLogs',
        ])->findOrFail($id);
        $roomData = new RoomResource($room);
        //return new RoomResource(resource: $room);
        return Inertia::render('panel/RoomFloor/indexRoomFloor', [
            'data' => $roomData,
        ]);
    }
    public function viewdetails($id)
{
    $room = Room::with([
        'floor.subBranch.branch',
        'roomType',
        'currentBooking',
        'statusLogs',
    ])->findOrFail($id);

    return response()->json([
        'success' => true,
        'data' => new RoomResource($room),
    ]);
}

}
