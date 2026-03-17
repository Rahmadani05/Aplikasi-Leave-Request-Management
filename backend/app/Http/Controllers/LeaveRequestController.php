<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    // Ambil daftar request
    public function index(Request $request)
    {
        $query = LeaveRequest::with(['leaveType']);
        
        if ($request->user()->role === 'user') {
            $query->where('user_id', $request->user()->id); 
        }
        
        return response()->json(['data' => $query->orderBy('created_at', 'desc')->get()]); 
    }

    // User Submit Pengajuan Cuti
    public function store(Request $request)
    {
        if ($request->user()->role !== 'user') {
            return response()->json(['message' => 'Hanya user yang bisa mengajukan cuti'], 403);
        }

        $validated = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date|after_or_equal:today', 
            'end_date' => 'required|date|after_or_equal:start_date', 
            'reason' => 'required|string',
        ], [
            'start_date.after_or_equal' => 'Tanggal mulai tidak boleh sebelum tanggal mulai',
            'end_date.after_or_equal' => 'Tanggal selesai harus setelah atau sama dengan tanggal mulai'
        ]);

        $user = $request->user();
        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);
        
        // Hitung total hari
        $totalDays = $start->diffInDays($end) + 1;

        // Validasi Kuota
        $balance = LeaveBalance::where('user_id', $user->id)
            ->where('leave_type_id', $validated['leave_type_id'])
            ->where('year', date('Y'))
            ->first();

        $remaining = $balance ? ($balance->total_quota - $balance->used) : 0;
        if ($totalDays > $remaining) {
            return response()->json(['message' => 'Sisa kuota cuti tidak mencukupi untuk jumlah hari yang diajukan'], 400); 
        }

        // Validasi Overlap (bentrok dengan pending/approved)
        $hasOverlap = LeaveRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved']) // 
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_date', [$start, $end])
                      ->orWhereBetween('end_date', [$start, $end])
                      ->orWhere(function ($q) use ($start, $end) {
                          $q->where('start_date', '<=', $start)
                            ->where('end_date', '>=', $end);
                      });
            })->exists();

        if ($hasOverlap) {
            return response()->json(['message' => 'Tanggal pengajuan bentrok dengan cuti Anda yang lain (Pending/Approved)'], 400); 
        }

        $leaveRequest = LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $validated['leave_type_id'],
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'total_days' => $totalDays,
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'Pengajuan cuti berhasil disubmit', 'data' => $leaveRequest], 201);
    }

    // Admin Approve / Reject
    public function respond(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'admin_notes' => 'nullable|string'
        ]);

        $leaveRequest = LeaveRequest::findOrFail($id);

        if ($leaveRequest->status !== 'pending') {
            return response()->json(['message' => 'Hanya request berstatus pending yang dapat direspon'], 400); 
        }

        $leaveRequest->status = $validated['status'];
        $leaveRequest->responded_by = $request->user()->id;
        $leaveRequest->admin_notes = $validated['admin_notes'];
        $leaveRequest->responded_at = now();

        // Jika di-approve, kurangi balance user
        if ($validated['status'] === 'approved') {
            $balance = LeaveBalance::where('user_id', $leaveRequest->user_id)
                ->where('leave_type_id', $leaveRequest->leave_type_id)
                ->where('year', date('Y'))
                ->first();
            
            $balance->used += $leaveRequest->total_days;
            $balance->save();
        }

        $leaveRequest->save();

        return response()->json(['message' => 'Request berhasil di-' . $validated['status'], 'data' => $leaveRequest]);
    }

    // User Cancel Request
    public function cancel(Request $request, $id)
    {
        $leaveRequest = LeaveRequest::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();

        if ($leaveRequest->status !== 'pending') {
            return response()->json(['message' => 'Hanya request berstatus pending yang dapat dibatalkan'], 400); 
        }

        $leaveRequest->status = 'cancelled';
        $leaveRequest->save(); // Balance tetap

        return response()->json(['message' => 'Pengajuan cuti berhasil dibatalkan', 'data' => $leaveRequest]);
    }

    // Soft Delete Request
    public function destroy(Request $request, $id)
    {
        $leaveRequest = LeaveRequest::findOrFail($id);
        $user = $request->user();

        // Cek aturan hapus untuk Admin
        if ($user->role === 'admin') {
            if ($leaveRequest->status === 'pending') {
                return response()->json(['message' => 'Request pending tidak dapat dihapus, harus dicancel dulu'], 400);
            }
        } 
        // Cek aturan hapus untuk User
        else {
            if ($leaveRequest->user_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            if (!in_array($leaveRequest->status, ['cancelled', 'rejected'])) {
                return response()->json(['message' => 'Anda hanya dapat menghapus request yang berstatus cancelled atau rejected'], 400); 
            }
        }

        $leaveRequest->deleted_by = $user->id; 
        $leaveRequest->save();
        $leaveRequest->delete(); // Soft delete mengisi deleted_at

        return response()->json(['message' => 'Data cuti berhasil dihapus (soft delete)']);
    }
}