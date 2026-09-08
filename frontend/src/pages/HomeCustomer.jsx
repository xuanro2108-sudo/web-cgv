import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import "./HomeCustomer.css";
import Header from "../components/common/Header/Header";

function HomeCustomer() {
  const navigate = useNavigate();

  const [logoutLoading, setLogoutLoading] = useState(false); // eslint-disable-line no-unused-vars
  const [movies, setMovies] = useState([]);
  const [moviesLoading, setMoviesLoading] = useState(true);
  const [moviesError, setMoviesError] = useState("");
  const [activeMovieTab, setActiveMovieTab] = useState("showing");

  useEffect(() => {
    const controller = new AbortController();

    const loadMovies = async () => {
      try {
        const response = await fetch(
          "http://127.0.0.1:8000/api/phims",
          {
            headers: {
              Accept: "application/json",
            },
            signal: controller.signal,
          }
        );

        const data = await response.json();

        if (!response.ok) {
          throw new Error(
            data.message || "Không thể tải danh sách phim."
          );
        }

        setMovies(Array.isArray(data.data) ? data.data : []);
      } catch (error) {
        if (error.name !== "AbortError") {
          console.error("Lỗi tải danh sách phim:", error);
          setMoviesError(
            "Không thể tải danh sách phim. Vui lòng kiểm tra máy chủ."
          );
        }
      } finally {
        if (!controller.signal.aborted) {
          setMoviesLoading(false);
        }
      }
    };

    loadMovies();

    return () => controller.abort();
  }, []);

  const visibleMovies = movies.filter((movie) => {
    if (activeMovieTab === "upcoming") {
      return movie.trangThai === "SAP_CHIEU";
    }

    if (activeMovieTab === "showing") {
      return movie.trangThai !== "SAP_CHIEU";
    }

    return true;
  });

  // =========================
  // ĐĂNG XUẤT
  // =========================
  const handleLogout = async () => { // eslint-disable-line no-unused-vars
    const token = localStorage.getItem("token");

    if (!token) {
      clearLoginData();
      navigate("/");
      return;
    }

    setLogoutLoading(true);

    try {
      const response = await fetch(
        "http://127.0.0.1:8000/api/auth/logout",
        {
          method: "POST",

          headers: {
            Accept: "application/json",
            Authorization: `Bearer ${token}`,
          },
        }
      );

      const data = await response.json();

      if (!response.ok) {
        alert(
          data.message || "Đăng xuất không thành công."
        );

        return;
      }

      // Xóa thông tin đăng nhập ở trình duyệt
      clearLoginData();

      // Chuyển về trang đăng nhập
      navigate("/");

    } catch (error) {
      console.error(
        "Lỗi đăng xuất:",
        error
      );

      alert(
        "Không thể kết nối đến máy chủ."
      );

    } finally {
      setLogoutLoading(false);
    }
  };

  // =========================
  // XÓA LOCAL STORAGE
  // =========================
  const clearLoginData = () => {
    localStorage.removeItem("token");
    localStorage.removeItem("vaiTro");
    localStorage.removeItem("taiKhoan");
    localStorage.removeItem("khachHang");
  };

  return (
    <div className="home-customer">

    <Header />

      <main className="home-content">
        <section className="home-hero">
          <div className="hero-copy">
            <span>CGV AEON MALL HÀ ĐÔNG</span>
            <h1>Trải nghiệm điện ảnh<br />theo cách của bạn</h1>
            <p>Khám phá những bộ phim mới nhất và đặt vé ngay hôm nay.</p>
            <button type="button" onClick={() => document.querySelector(".home-movies")?.scrollIntoView({ behavior: "smooth" })}>
              XEM PHIM NGAY
            </button>
          </div>
          <div className="hero-decoration" aria-hidden="true">CGV</div>
        </section>

        <section className="home-movies">
          <div className="movie-tabs" role="tablist" aria-label="Danh mục phim">
            <button
              className={activeMovieTab === "upcoming" ? "active" : ""}
              onClick={() => setActiveMovieTab("upcoming")}
              type="button"
            >
              PHIM SẮP CHIẾU
            </button>
            <button
              className={activeMovieTab === "showing" ? "active" : ""}
              onClick={() => setActiveMovieTab("showing")}
              type="button"
            >
              PHIM ĐANG CHIẾU
            </button>
            <button
              className={activeMovieTab === "all" ? "active" : ""}
              onClick={() => setActiveMovieTab("all")}
              type="button"
            >
              TẤT CẢ PHIM
            </button>
          </div>

          {moviesLoading && <p className="movie-message">Đang tải danh sách phim...</p>}
          {!moviesLoading && moviesError && <p className="movies-error">{moviesError}</p>}
          {!moviesLoading && !moviesError && visibleMovies.length === 0 && (
            <p className="movie-message">Hiện chưa có phim trong danh mục này.</p>
          )}

          {!moviesLoading && !moviesError && visibleMovies.length > 0 && (
            <div className="movie-grid">
              {visibleMovies.map((movie) => (
                <article className="movie-card" key={movie.maPhim}>
                  {movie.hinhAnh ? (
                    <img src={movie.hinhAnh} alt={movie.tenPhim} className="movie-poster" />
                  ) : (
                    <div className="movie-poster movie-poster-empty">Chưa có ảnh</div>
                  )}
                  <div className="movie-card-content">
                    <h3>{movie.tenPhim}</h3>
                    {movie.theLoai && <p>{movie.theLoai}</p>}
                    {movie.thoiLuong && <span>{movie.thoiLuong} phút</span>}
                  </div>
                </article>
              ))}
            </div>
          )}
        </section>
      </main>

    </div>
  );
}

export default HomeCustomer;