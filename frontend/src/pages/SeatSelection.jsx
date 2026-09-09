import { useEffect, useMemo, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import "./SeatSelection.css";
import { createOrder, getShowtime, reserveSeat } from "../services/movieService";

const getSeatLabel = (seat) => `${seat.hang || "?"}${seat.cot ?? "?"}`;
const formatShowDate = (date) => {
    const datePart = String(date || "").match(/^\d{4}-\d{2}-\d{2}/)?.[0];

    if (!datePart) {
        return "Đang cập nhật";
    }

    const [year, month, day] = datePart.split("-");
    return `${day}/${month}/${year}`;
};

function SeatSelection() {
    const { maLichChieu } = useParams();
    const navigate = useNavigate();
    const [showtime, setShowtime] = useState(null);
    const [selectedSeats, setSelectedSeats] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");
    const [secondsLeft, setSecondsLeft] = useState(600);
    const [submitting, setSubmitting] = useState(false);

    useEffect(() => {
        getShowtime(maLichChieu)
            .then((response) => setShowtime(response.data))
            .catch((loadError) => {
                console.error("Lỗi tải sơ đồ ghế:", loadError);
                setError("Không thể tải sơ đồ ghế.");
            })
            .finally(() => setLoading(false));
    }, [maLichChieu]);

    useEffect(() => {
        const timer = window.setInterval(() => {
            setSecondsLeft((current) => {
                if (current <= 1) {
                    window.clearInterval(timer);
                    navigate("/home", { replace: true });
                    return 0;
                }

                return current - 1;
            });
        }, 1000);

        return () => window.clearInterval(timer);
    }, [navigate]);

    const seats = useMemo(
        () =>
            showtime?.phong_chieu?.so_do_ghe?.ghes ||
            showtime?.phongChieu?.soDoGhe?.ghes ||
            [],
        [showtime]
    );
    const rows = useMemo(
        () =>
            seats.reduce((result, seat) => {
                const row = seat.hang || "?";
                result[row] = [...(result[row] || []), seat].sort(
                    (a, b) => a.cot - b.cot
                );
                return result;
            }, {}),
        [seats]
    );
    const seatGroups = useMemo(
        () =>
            Object.entries(rows).map(([row, rowSeats]) => {
                const groups = [];

                rowSeats.forEach((seat) => {
                    const previous = groups[groups.length - 1];
                    const isPair =
                        seat.loaiGhe === "DOI" &&
                        previous?.length === 1 &&
                        previous[0].loaiGhe === "DOI" &&
                        previous[0].cot + 1 === seat.cot;

                    if (isPair) {
                        previous.push(seat);
                    } else {
                        groups.push([seat]);
                    }
                });

                return [row, groups];
            }),
        [rows]
    );
    const seatPrices = useMemo(() => {
        const basePrice = Number(showtime?.giaVeCoBan || 0);
        const vipPrice = basePrice * 1.2;

        return {
            THUONG: basePrice,
            VIP: vipPrice,
            DOI: vipPrice * 2,
        };
    }, [showtime]);
    const total = useMemo(() => {
        const coupleSeatCount = selectedSeats.filter(
            (seat) => seat.loaiGhe === "DOI"
        ).length;
        const regularTotal = selectedSeats
            .filter((seat) => seat.loaiGhe !== "DOI")
            .reduce((sum, seat) => sum + (seatPrices[seat.loaiGhe] || 0), 0);

        return regularTotal + (coupleSeatCount / 2) * seatPrices.DOI;
    }, [selectedSeats, seatPrices]);

    const toggleSeatGroup = (seatGroup) => {
        if (seatGroup.some((seat) => seat.trangThai !== "HOAT_DONG")) {
            return;
        }

        const groupIds = seatGroup.map((seat) => seat.maGhe);
        setSelectedSeats((current) => {
            const isSelected = seatGroup.every((seat) =>
                current.some((item) => item.maGhe === seat.maGhe)
            );

            return isSelected
                ? current.filter((item) => !groupIds.includes(item.maGhe))
                : [...current, ...seatGroup];
        });
    };

    const continueToPayment = async () => {
        if (!selectedSeats.length || submitting) {
            return;
        }

        setSubmitting(true);
        setError("");
        try {
            const orderResponse = await createOrder();
            const order = orderResponse.data;
            const seatsToReserve = selectedSeats.filter(
                (seat) =>
                    seat.loaiGhe !== "DOI" ||
                    !selectedSeats.some(
                        (other) =>
                            other.loaiGhe === "DOI" &&
                            other.hang === seat.hang &&
                            other.cot === seat.cot - 1
                    )
            );

            for (const seat of seatsToReserve) {
                await reserveSeat(order.maDonHang, maLichChieu, seat.maGhe);
            }

            navigate(`/chon-combo/${order.maDonHang}`);
        } catch (submitError) {
            console.error("Lỗi giữ ghế:", submitError);
            setError(submitError.message);
        } finally {
            setSubmitting(false);
        }
    };

    if (loading) {
        return <p className="seat-message">Đang tải sơ đồ ghế...</p>;
    }

    if (error || !showtime) {
        return <p className="seat-message seat-error">{error || "Không tìm thấy suất chiếu."}</p>;
    }

    return (
        <div className="seat-page">
            <div className="seat-container">
                <div className="seat-layout">
                    <main className="seat-map-panel">
                        <h1>Chọn ghế</h1>
                        <p className="seat-showtime">
                            {showtime.phim?.tenPhim} · {showtime.gioBatDau}
                        </p>
                        <div className="seat-legend seat-legend-top">
                            <div className="seat-legend-row seat-types">
                                <span><i className="seat free" /> Ghế thường</span>
                                <span><i className="seat vip" /> Ghế VIP</span>
                                <span><i className="seat doi couple" /> Ghế đôi</span>
                            </div>
                            <div className="seat-legend-row seat-status">
                                <span><i className="seat selected" /> Đang chọn</span>
                                <span><i className="seat occupied" /> Đã bán</span>
                            </div>
                        </div>
                        <div className="screen">MÀN HÌNH CHIẾU</div>
                        <div className="seat-rows">
                            {seatGroups.map(([row, groups]) => (
                                <div className="seat-row" key={row}>
                                    {groups.map((seatGroup) => {
                                            const isSelected = seatGroup.every((seat) =>
                                                selectedSeats.some(
                                                    (item) => item.maGhe === seat.maGhe
                                                )
                                            );
                                            const isOccupied = seatGroup.some(
                                                (seat) => seat.trangThai !== "HOAT_DONG"
                                            );
                                            const firstSeat = seatGroup[0];
                                            return (
                                                <button
                                                    type="button"
                                                    key={seatGroup.map((seat) => seat.maGhe).join("-")}
                                                    className={`seat ${firstSeat.loaiGhe.toLowerCase()} ${seatGroup.length > 1 ? "couple" : ""} ${isSelected ? "selected" : ""} ${isOccupied ? "occupied" : "free"}`}
                                                    onClick={() => toggleSeatGroup(seatGroup)}
                                                    disabled={isOccupied}
                                                >
                                                    {seatGroup.map(getSeatLabel).join(" - ")}
                                                </button>
                                            );
                                        })}
                                </div>
                            ))}
                        </div>
                        <div className="seat-footer">
                            <div className="seat-price-note">
                                <strong>Giá vé</strong>
                                <span>
                                    Thường {seatPrices.THUONG.toLocaleString("vi-VN")}đ · VIP{" "}
                                    {seatPrices.VIP.toLocaleString("vi-VN")}đ · Đôi{" "}
                                    {seatPrices.DOI.toLocaleString("vi-VN")}đ
                                </span>
                            </div>
                            <div className="seat-footer-total">
                                <strong>Tổng tiền</strong>
                                <b>{total.toLocaleString("vi-VN")} đ</b>
                            </div>
                            <div className="seat-countdown">
                                <strong>Thời gian còn lại</strong>
                                <b>{Math.floor(secondsLeft / 60)}:{String(secondsLeft % 60).padStart(2, "0")}</b>
                            </div>
                        </div>
                    </main>
                    <aside className="seat-summary">
                        {showtime.phim?.hinhAnh && (
                            <img
                                className="seat-movie-poster"
                                src={showtime.phim.hinhAnh}
                                alt={`Poster ${showtime.phim.tenPhim}`}
                            />
                        )}
                        <h2>{showtime.phim?.tenPhim}</h2>
                        <p>Suất chiếu: {showtime.gioBatDau}</p>
                        <p>Thể loại: {showtime.phim?.theLoai || "Đang cập nhật"}</p>
                        <p>
                            Thời lượng:{" "}
                            {showtime.phim?.thoiLuong
                                ? `${showtime.phim.thoiLuong} phút`
                                : "Đang cập nhật"}
                        </p>
                        <p>Ngày chiếu: {formatShowDate(showtime.ngayChieu)}</p>
                        <p>
                            Ghế đã chọn:{" "}
                            {selectedSeats.map(getSeatLabel).join(", ") || "Chưa chọn"}
                        </p>
                        {error && <p className="seat-error">{error}</p>}
                        <button className="continue-button" type="button" disabled={!selectedSeats.length || submitting} onClick={continueToPayment}>
                            {submitting ? "ĐANG GIỮ GHẾ..." : "TIẾP TỤC"}
                        </button>
                    </aside>
                </div>
            </div>
        </div>
    );
}

export default SeatSelection;
