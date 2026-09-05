<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(
        Request $request,
        Closure $next,
        string ...$roles
    ): Response {
        $taiKhoan = $request->user();

        // Chưa đăng nhập
        if (!$taiKhoan) {
            return response()->json([
                'message' => 'Bạn chưa đăng nhập'
            ], 401);
        }

        // Không đúng vai trò
        if (!in_array($taiKhoan->vaiTro, $roles, true)) {
            return response()->json([
                'message' => 'Bạn không có quyền thực hiện chức năng này'
            ], 403);
        }

        return $next($request);
    }
}