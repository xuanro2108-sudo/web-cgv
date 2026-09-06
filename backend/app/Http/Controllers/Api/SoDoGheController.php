<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SoDoGhe;
use Illuminate\Http\Request;

class SoDoGheController extends Controller
{
    // Xem danh sách sơ đồ ghế
    public function index()
    {
        $soDoGhes = SoDoGhe::with(['phongChieu', 'ghes'])
            ->orderBy('maSoDo')
            ->paginate(10);

        return response()->json($soDoGhes);
    }

    // Xem sơ đồ ghế của một phòng
    public function show(string $maSoDo)
    {
        $soDoGhe = SoDoGhe::with(['phongChieu', 'ghes'])
            ->findOrFail($maSoDo);

        return response()->json([
            'message' => 'Lấy thông tin sơ đồ ghế thành công',
            'data' => $soDoGhe,
        ]);
    }
}