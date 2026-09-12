export default function CounterSaleStepper({
  step,
  setStep,
  selectedMovie,
  selectedShowtime,
  selectedSeats,
}) {
  return (
    <nav className="counter-stepper" aria-label="Các bước bán vé">
      <button
        type="button"
        className={`counter-step ${step === 1 ? "active" : step > 1 ? "completed" : ""}`}
        onClick={() => setStep(1)}
      >
        <span className="step-num">{step > 1 ? "✓" : "1"}</span>
        <span className="step-label">Chọn phim</span>
      </button>
      <span className="step-arrow">→</span>

      <button
        type="button"
        className={`counter-step ${step === 2 ? "active" : step > 2 ? "completed" : ""}`}
        disabled={!selectedMovie}
        onClick={() => selectedMovie && setStep(2)}
      >
        <span className="step-num">{step > 2 ? "✓" : "2"}</span>
        <span className="step-label">
          {selectedMovie ? selectedMovie.tenPhim : "Chọn suất chiếu"}
        </span>
      </button>
      <span className="step-arrow">→</span>

      <button
        type="button"
        className={`counter-step ${step === 3 ? "active" : step > 3 ? "completed" : ""}`}
        disabled={!selectedShowtime}
        onClick={() => selectedShowtime && setStep(3)}
      >
        <span className="step-num">{step > 3 ? "✓" : "3"}</span>
        <span className="step-label">
          {selectedSeats.length > 0 ? `Ghế (${selectedSeats.length})` : "Chọn ghế"}
        </span>
      </button>
      <span className="step-arrow">→</span>

      <button
        type="button"
        className={`counter-step ${step === 4 ? "active" : step > 4 ? "completed" : ""}`}
        disabled={selectedSeats.length === 0}
        onClick={() => selectedSeats.length > 0 && setStep(4)}
      >
        <span className="step-num">{step > 4 ? "✓" : "4"}</span>
        <span className="step-label">Bỏng nước / Combo</span>
      </button>
      <span className="step-arrow">→</span>

      <button
        type="button"
        className={`counter-step ${step === 5 ? "active" : step > 5 ? "completed" : ""}`}
        disabled={selectedSeats.length === 0}
        onClick={() => selectedSeats.length > 0 && setStep(5)}
      >
        <span className="step-num">{step > 5 ? "✓" : "5"}</span>
        <span className="step-label">Thanh toán</span>
      </button>
    </nav>
  );
}
