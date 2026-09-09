import { useEffect, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import "./MovieShowtimes.css";
import { getMovies, getShowtimes } from "../services/movieService";

function normalizeShowtimeDate(value) {
    if (typeof value !== "string") {
        return "";
    }

    const match = value.match(/^(\d{4}-\d{2}-\d{2})/);
    return match ? match[1] : "";
}

function formatShowtimeDate(date) {
    const [year, month, day] = date.split("-").map(Number);
    const dateObject = new Date(year, month - 1, day);
    const weekdays = ["CN", "T2", "T3", "T4", "T5", "T6", "T7"];

    return {
        day: String(day).padStart(2, "0"),
        month: String(month).padStart(2, "0"),
        weekday: weekdays[dateObject.getDay()],
    };
}

function MovieShowtimes() {
    const { maPhim } = useParams();
    const navigate = useNavigate();
    const [movie, setMovie] = useState(null);
    const [showtimes, setShowtimes] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");
    const [selectedDate, setSelectedDate] = useState("");

    useEffect(() => {
        const loadShowtimes = async () => {
            try {
                const [moviesResponse, showtimesResponse] = await Promise.all([
                    getMovies(),
                    getShowtimes(),
                ]);
                const movies = Array.isArray(moviesResponse)
                    ? moviesResponse
                    : moviesResponse.data || [];
                const showtimesData = Array.isArray(showtimesResponse)
                    ? showtimesResponse
                    : showtimesResponse.data || [];

                setMovie(movies.find((item) => item.maPhim === maPhim) || null);
                const movieShowtimes = showtimesData.filter(
                        (showtime) =>
                            showtime.maPhim === maPhim &&
                            showtime.trangThai === "HOAT_DONG"
                    );
                const dates = [
                    ...new Set(
                        movieShowtimes.map((showtime) =>
                            normalizeShowtimeDate(showtime.ngayChieu)
                        )
                    ),
                ]
                    .filter(Boolean)
                    .sort();
                setShowtimes(movieShowtimes);
                setSelectedDate(dates[0] || "");
            } catch (loadError) {
                console.error("Lỗi tải lịch chiếu:", loadError);
                setError("Không thể tải lịch chiếu. Vui lòng thử lại.");
            } finally {
                setLoading(false);
            }
        };

        loadShowtimes();
    }, [maPhim]);

    return (
        <div className="showtimes-page">
            <div className="showtimes-container">
                {loading && <p className="showtimes-message">Đang tải suất chiếu...</p>}
                {!loading && error && <p className="showtimes-error">{error}</p>}
                {!loading && !error && !movie && (
                    <p className="showtimes-error">Không tìm thấy thông tin phim.</p>
                )}

                {!loading && !error && movie && (
                    <>
                        <div className="showtimes-heading">
                            <div>
                                <span>ĐẶT VÉ</span>
                                <h1>{movie.tenPhim}</h1>
                                <p>
                                    {movie.theLoai || "Đang cập nhật"} ·{" "}
                                    {movie.thoiLuong
                                        ? `${movie.thoiLuong} phút`
                                        : "Thời lượng đang cập nhật"}
                                </p>
                            </div>
                        </div>

                        <section className="showtimes-panel">
                            <div className="showtimes-title">
                                <h2>Chọn suất chiếu</h2>
                                <span>Chọn ngày để xem suất chiếu</span>
                            </div>
                            <div className="showtimes-dates" role="tablist">
                                {[
                                    ...new Set(
                                        showtimes.map((showtime) =>
                                            normalizeShowtimeDate(showtime.ngayChieu)
                                        )
                                    ),
                                ]
                                    .filter(Boolean)
                                    .sort()
                                    .map((date) => {
                                        const formattedDate = formatShowtimeDate(date);
                                        return (
                                            <button
                                                type="button"
                                                className={selectedDate === date ? "active" : ""}
                                                key={date}
                                                onClick={() => setSelectedDate(date)}
                                            >
                                                <strong>{formattedDate.day}</strong>
                                                <span>/{formattedDate.month}</span>
                                                <small>- {formattedDate.weekday}</small>
                                            </button>
                                        );
                                    })}
                            </div>
                            {showtimes.length === 0 ? (
                                <p className="showtimes-message">
                                    Phim hiện chưa có suất chiếu.
                                </p>
                            ) : (
                                <div className="showtimes-list">
                                    {showtimes
                                        .filter(
                                            (showtime) =>
                                                normalizeShowtimeDate(showtime.ngayChieu) ===
                                                selectedDate
                                        )
                                        .map((showtime) => (
                                        <button
                                            type="button"
                                            className="showtime-button"
                                            key={showtime.maLichChieu}
                                            onClick={() =>
                                                navigate(`/chon-ghe/${showtime.maLichChieu}`)
                                            }
                                        >
                                            <strong>{showtime.gioBatDau}</strong>
                                            <span>{showtime.gheTrong ?? 0} ghế trống</span>
                                        </button>
                                    ))}
                                </div>
                            )}
                        </section>
                    </>
                )}
            </div>
        </div>
    );
}

export default MovieShowtimes;
