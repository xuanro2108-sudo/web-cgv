<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <title>Vé xem phim</title>
</head>
<body style="margin:0;background:#eef1f3;color:#263238;font-family:Arial,sans-serif;line-height:1.45">
@php
    $ticket = $order->veGhes->first();
    $showtime = $ticket?->lichChieu;
    $movie = $showtime?->phim;
    $room = $showtime?->phongChieu;
    $seats = $order->veGhes
        ->map(fn ($item) => ($item->ghe?->hang ?? '').($item->ghe?->cot ?? ''))
        ->filter()
        ->join(', ');
    $ticketTotal = $order->veGhes->sum('giaVe');
    $comboTotal = $order->chiTietComboDonHangs->sum('thanhTien');
    $subtotal = $ticketTotal + $comboTotal;
    $discount = max(0, $subtotal - (float) $payment->soTien);
@endphp
<div style="width:100%;padding:24px 0">
    <div style="max-width:620px;margin:0 auto;background:#fff">
        <div style="padding:24px 24px 18px;text-align:center;border-bottom:8px dotted #eef1f3">
            <div style="font-size:22px;font-weight:700;color:#b71c1c">{{ $movie?->tenPhim ?? 'VÉ XEM PHIM' }}</div>
            <div style="margin-top:8px;color:#607d8b;font-size:12px">Vé điện tử - vui lòng xuất trình mã QR khi đến rạp</div>
        </div>

        <div style="padding:22px 24px;text-align:center;border-bottom:8px dotted #eef1f3">
            <div style="font-size:12px;color:#607d8b">MÃ VÉ (RESERVATION CODE)</div>
            <div style="margin:5px 0 12px;font-size:23px;font-weight:700;letter-spacing:1px;color:#263238">{{ $order->maQR }}</div>
            <div style="font-size:13px;color:#607d8b">
                Suất chiếu: <strong style="color:#263238">{{ $showtime?->ngayChieu?->format('d/m/Y') }} · {{ $showtime?->gioBatDau?->format('H:i') }}</strong>
            </div>
            <img src="{{ $ticketQrUrl }}" alt="Mã QR vé xem phim" width="240" height="240" style="display:block;margin:18px auto 10px">
            <div style="margin-top:18px;padding:12px;text-align:left;background:#f5f7f8;border-left:3px solid #c62828;font-size:12px;color:#546e7a">
                Quý khách vui lòng tới quầy dịch vụ và xuất trình mã vé này để nhận vé.
            </div>
        </div>

        <div style="padding:18px 24px">
            <table style="width:100%;border-collapse:collapse;font-size:13px">
                <tr><td style="padding:9px 0;border-bottom:1px dashed #b0bec5;color:#607d8b">Phòng chiếu<br><i>Screen</i></td><td style="padding:9px 0;border-bottom:1px dashed #b0bec5;text-align:right;font-weight:700">{{ $room?->tenPhong ?? 'Đang cập nhật' }}</td></tr>
                <tr><td style="padding:9px 0;border-bottom:1px dashed #b0bec5;color:#607d8b">Ghế<br><i>Seat</i></td><td style="padding:9px 0;border-bottom:1px dashed #b0bec5;text-align:right;font-weight:700">{{ $seats ?: 'Đang cập nhật' }}</td></tr>
                <tr><td style="padding:9px 0;border-bottom:1px dashed #b0bec5;color:#607d8b">Thời gian thanh toán<br><i>Payment time</i></td><td style="padding:9px 0;border-bottom:1px dashed #b0bec5;text-align:right;font-weight:700">{{ $payment->ngayThanhToan?->format('d/m/Y H:i') }}</td></tr>
                <tr><td style="padding:9px 0;border-bottom:1px dashed #b0bec5;color:#607d8b">Tiền combo bắp nước<br><i>Concession amount</i></td><td style="padding:9px 0;border-bottom:1px dashed #b0bec5;text-align:right;font-weight:700">{{ number_format($comboTotal, 0, ',', '.') }} VND</td></tr>
                <tr><td style="padding:9px 0;border-bottom:1px dashed #b0bec5;color:#607d8b">Tổng tiền<br><i>Total amount</i></td><td style="padding:9px 0;border-bottom:1px dashed #b0bec5;text-align:right;font-weight:700">{{ number_format($subtotal, 0, ',', '.') }} VND</td></tr>
                <tr><td style="padding:9px 0;border-bottom:1px dashed #b0bec5;color:#607d8b">Số tiền giảm giá<br><i>Discount amount</i></td><td style="padding:9px 0;border-bottom:1px dashed #b0bec5;text-align:right;font-weight:700">{{ number_format($discount, 0, ',', '.') }} VND</td></tr>
                <tr><td style="padding:12px 0 4px;color:#c62828;font-weight:700">Số tiền thanh toán<br><i>Payment amount</i></td><td style="padding:12px 0 4px;text-align:right;color:#c62828;font-size:16px;font-weight:700">{{ number_format((float) $payment->soTien, 0, ',', '.') }} VND</td></tr>
            </table>
        </div>
    </div>
</div>
</body>
</html>
