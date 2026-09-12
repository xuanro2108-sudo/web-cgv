export default function MovieStep({
  filteredMovies,
  movies,
  allShowtimes,
  movieSearch,
  setMovieSearch,
  movieTab,
  setMovieTab,
  handleSelectMovie,
}) {
  return (
    <section className="counter-step-view">
      <div className="counter-filter-bar">
        <div className="counter-search-box">
          <span className="search-icon">🔍</span>
          <input
            type="text"
            placeholder="Tìm tên phim nhanh..."
            value={movieSearch}
            onChange={(e) => setMovieSearch(e.target.value)}
            autoFocus
          />
          {movieSearch && (
            <button
              type="button"
              className="search-clear"
              onClick={() => setMovieSearch("")}
            >
              ✕
            </button>
          )}
        </div>

        <div className="counter-tabs">
          <button
            type="button"
            className={movieTab === "all" ? "active" : ""}
            onClick={() => setMovieTab("all")}
          >
            Tất cả ({movies.length})
          </button>
          <button
            type="button"
            className={movieTab === "showing" ? "active" : ""}
            onClick={() => setMovieTab("showing")}
          >
            Đang chiếu
          </button>
          <button
            type="button"
            className={movieTab === "upcoming" ? "active" : ""}
            onClick={() => setMovieTab("upcoming")}
          >
            Sắp chiếu
          </button>
        </div>
      </div>

      {filteredMovies.length === 0 ? (
        <div className="counter-empty-message">
          Không tìm thấy phim nào phù hợp với từ khóa "{movieSearch}".
        </div>
      ) : (
        <div className="counter-movie-grid">
          {filteredMovies.map((movie) => {
            const showtimeCount = allShowtimes.filter(
              (item) => item.maPhim === movie.maPhim && item.trangThai === "HOAT_DONG"
            ).length;

            return (
              <article
                className="counter-movie-card"
                key={movie.maPhim}
                onClick={() => handleSelectMovie(movie)}
              >
                <div className="movie-poster-wrap">
                  {movie.hinhAnh ? (
                    <img src={movie.hinhAnh} alt={movie.tenPhim} />
                  ) : (
                    <div className="movie-no-poster">Chưa có ảnh</div>
                  )}
                  <span className="movie-showtimes-badge">
                    {showtimeCount > 0 ? `${showtimeCount} suất chiếu` : "Chưa có suất"}
                  </span>
                </div>
                <div className="movie-card-info">
                  <h3>{movie.tenPhim}</h3>
                  <p className="movie-meta">
                    {movie.theLoai || "Chưa cập nhật thể loại"}
                    {movie.thoiLuong ? ` · ${movie.thoiLuong} phút` : ""}
                  </p>
                  <button
                    type="button"
                    className="counter-btn-select-movie"
                    onClick={(e) => {
                      e.stopPropagation();
                      handleSelectMovie(movie);
                    }}
                  >
                    Chọn phim này →
                  </button>
                </div>
              </article>
            );
          })}
        </div>
      )}
    </section>
  );
}
