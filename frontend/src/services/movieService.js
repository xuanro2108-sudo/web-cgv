const API_URL = "http://127.0.0.1:8000/api";

function authHeaders() {
    const token = localStorage.getItem("token");

    return {
        Accept: "application/json",
        "Content-Type": "application/json",
        ...(token ? { Authorization: "Bearer " + token } : {}),
    };
}

export async function getMovies() {
    const response = await fetch(`${API_URL}/phims`, {
        headers: {
            Accept: "application/json",
        },
    });

    if (!response.ok) {
        throw new Error("Không thể tải danh sách phim.");
    }

    return await response.json();
}

export async function getShowtimes() {
    const response = await fetch(`${API_URL}/lich-chieus`, {
        headers: {
            Accept: "application/json",
        },
    });

    if (!response.ok) {
        throw new Error("Không thể tải lịch chiếu.");
    }

    return await response.json();
}

export async function getShowtime(maLichChieu) {
    const response = await fetch(`${API_URL}/lich-chieus/${maLichChieu}`, {
        headers: {
            Accept: "application/json",
        },
    });

    if (!response.ok) {
        throw new Error("Không thể tải sơ đồ ghế.");
    }

    return await response.json();
}

export async function createOrder() {
    const response = await fetch(`${API_URL}/don-hangs`, {
        method: "POST",
        headers: authHeaders(),
    });

    if (!response.ok) {
        const data = await response.json().catch(() => ({}));
        throw new Error(
            data.message || "Vui lòng đăng nhập để tiếp tục đặt vé."
        );
    }

    return await response.json();
}

export async function reserveSeat(maDonHang, maLichChieu, maGhe) {
    const response = await fetch(`${API_URL}/ve-ghes`, {
        method: "POST",
        headers: authHeaders(),
        body: JSON.stringify({ maDonHang, maLichChieu, maGhe }),
    });

    if (!response.ok) {
        const data = await response.json().catch(() => ({}));
        throw new Error(data.message || "Không thể giữ ghế đã chọn.");
    }

    return await response.json();
}

export async function getCombos() {
    const response = await fetch(`${API_URL}/combos`, {
        headers: authHeaders(),
    });

    if (!response.ok) {
        throw new Error("Không thể tải danh sách combo.");
    }

    return await response.json();
}

export async function getPromotions() {
    const response = await fetch(`${API_URL}/khuyen-mais`, {
        headers: authHeaders(),
    });

    if (!response.ok) {
        throw new Error("Không thể tải danh sách khuyến mại.");
    }

    return await response.json();
}

export async function getOrder(maDonHang) {
    const response = await fetch(`${API_URL}/don-hangs/${maDonHang}`, {
        headers: authHeaders(),
    });

    if (!response.ok) {
        throw new Error("Không thể tải thông tin đơn hàng.");
    }

    return await response.json();
}

export async function getCustomerProfile() {
    const response = await fetch(`${API_URL}/ho-so`, {
        headers: authHeaders(),
    });

    if (!response.ok) {
        throw new Error("Không thể tải thông tin khách hàng.");
    }

    return await response.json();
}

export async function createPayment(maDonHang, phuongThuc) {
    const response = await fetch(`${API_URL}/don-hangs/${maDonHang}/thanh-toan`, {
        method: "POST",
        headers: authHeaders(),
        body: JSON.stringify({ phuongThuc }),
    });
    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(data.message || "Không thể tạo yêu cầu thanh toán.");
    }

    return data;
}

export async function getPayment(maDonHang) {
    const response = await fetch(`${API_URL}/don-hangs/${maDonHang}/thanh-toan`, {
        headers: authHeaders(),
    });
    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(data.message || "Không thể kiểm tra trạng thái thanh toán.");
    }

    return data;
}

export async function simulatePayment(payment) {
    const response = await fetch(
        `${API_URL}/thanh-toans/${payment.maTT}/gia-lap`,
        {
            method: "POST",
            headers: authHeaders(),
            body: JSON.stringify({
                maGiaoDich: payment.maGiaoDich,
                ketQua: "THANH_CONG",
            }),
        }
    );
    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(data.message || "Thanh toán không thành công.");
    }

    return data;
}

export async function addCombo(maDonHang, maCombo, soLuong) {
    const response = await fetch(`${API_URL}/don-hangs/${maDonHang}/combos`, {
        method: "POST",
        headers: authHeaders(),
        body: JSON.stringify({ maCombo, soLuong }),
    });

    if (!response.ok) {
        const data = await response.json().catch(() => ({}));
        throw new Error(data.message || "Không thể thêm combo.");
    }

    return await response.json();
}

export async function updateCombo(maDonHang, maCombo, soLuong) {
    const response = await fetch(
        `${API_URL}/don-hangs/${maDonHang}/combos/${maCombo}`,
        {
            method: "PATCH",
            headers: authHeaders(),
            body: JSON.stringify({ soLuong }),
        }
    );

    if (!response.ok) {
        const data = await response.json().catch(() => ({}));
        throw new Error(data.message || "Không thể cập nhật combo.");
    }

    return await response.json();
}

export async function removeCombo(maDonHang, maCombo) {
    const response = await fetch(
        `${API_URL}/don-hangs/${maDonHang}/combos/${maCombo}`,
        { method: "DELETE", headers: authHeaders() }
    );

    if (!response.ok) {
        throw new Error("Không thể xóa combo.");
    }

    return await response.json();
}

export async function applyPromotion(maDonHang, maKM) {
    const response = await fetch(
        `${API_URL}/don-hangs/${maDonHang}/khuyen-mai`,
        {
            method: "POST",
            headers: authHeaders(),
            body: JSON.stringify({ maKM }),
        }
    );
    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(data.message || "Mã khuyến mại không hợp lệ.");
    }

    return data;
}

export async function removePromotion(maDonHang) {
    const response = await fetch(
        `${API_URL}/don-hangs/${maDonHang}/khuyen-mai`,
        { method: "DELETE", headers: authHeaders() }
    );

    if (!response.ok) {
        const data = await response.json().catch(() => ({}));
        throw new Error(data.message || "Không thể bỏ khuyến mại.");
    }

    return await response.json();
}