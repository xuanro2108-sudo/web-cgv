import { useNavigate } from "react-router-dom";
import "./CounterSale.css";

import useCounterSale from "../hooks/useCounterSale";
import CounterSaleHeader from "../components/admin/CounterSale/CounterSaleHeader";
import CounterSaleStepper from "../components/admin/CounterSale/CounterSaleStepper";
import MovieStep from "../components/admin/CounterSale/MovieStep";
import ShowtimeStep from "../components/admin/CounterSale/ShowtimeStep";
import SeatStep from "../components/admin/CounterSale/SeatStep";
import ComboStep from "../components/admin/CounterSale/ComboStep";
import PaymentStep from "../components/admin/CounterSale/PaymentStep";
import SuccessStep from "../components/admin/CounterSale/SuccessStep";

export default function CounterSale() {
  const navigate = useNavigate();
  const sale = useCounterSale();

  if (sale.loading) {
    return <div className="counter-loading-state">Đang tải dữ liệu quầy vé...</div>;
  }

  return (
    <div className="counter-sale-page">
      <CounterSaleHeader navigate={navigate} />

      <CounterSaleStepper
        step={sale.step}
        setStep={sale.setStep}
        selectedMovie={sale.selectedMovie}
        selectedShowtime={sale.selectedShowtime}
        selectedSeats={sale.selectedSeats}
      />

      {sale.error && (
        <div className="counter-alert-error" role="alert">
          <span>⚠️ {sale.error}</span>
          <button type="button" onClick={() => sale.setError("")}>✕</button>
        </div>
      )}

      {sale.step === 1 && (
        <MovieStep
          filteredMovies={sale.filteredMovies}
          movies={sale.movies}
          allShowtimes={sale.allShowtimes}
          movieSearch={sale.movieSearch}
          setMovieSearch={sale.setMovieSearch}
          movieTab={sale.movieTab}
          setMovieTab={sale.setMovieTab}
          handleSelectMovie={sale.handleSelectMovie}
        />
      )}

      {sale.step === 2 && sale.selectedMovie && (
        <ShowtimeStep
          selectedMovie={sale.selectedMovie}
          setStep={sale.setStep}
          availableDates={sale.availableDates}
          selectedDate={sale.selectedDate}
          setSelectedDate={sale.setSelectedDate}
          dateShowtimes={sale.dateShowtimes}
          loadingShowtime={sale.loadingShowtime}
          handleSelectShowtime={sale.handleSelectShowtime}
        />
      )}

      {sale.step === 3 && sale.selectedShowtime && (
        <SeatStep
          selectedMovie={sale.selectedMovie}
          selectedDate={sale.selectedDate}
          selectedShowtime={sale.selectedShowtime}
          setStep={sale.setStep}
          seatGroups={sale.seatGroups}
          selectedSeats={sale.selectedSeats}
          toggleSeatGroup={sale.toggleSeatGroup}
          seatPrices={sale.seatPrices}
          ticketTotal={sale.ticketTotal}
        />
      )}

      {sale.step === 4 && (
        <ComboStep
          combos={sale.combos}
          selectedCombos={sale.selectedCombos}
          updateComboQty={sale.updateComboQty}
          selectedMovie={sale.selectedMovie}
          selectedSeats={sale.selectedSeats}
          ticketTotal={sale.ticketTotal}
          grandTotal={sale.grandTotal}
          setStep={sale.setStep}
        />
      )}

      {sale.step === 5 && (
        <PaymentStep
          customer={sale.customer}
          setCustomer={sale.setCustomer}
          paymentMethod={sale.paymentMethod}
          setPaymentMethod={sale.setPaymentMethod}
          paymentConfig={sale.paymentConfig}
          pendingOrder={sale.pendingOrder}
          selectedMovie={sale.selectedMovie}
          selectedDate={sale.selectedDate}
          selectedShowtime={sale.selectedShowtime}
          selectedSeats={sale.selectedSeats}
          ticketTotal={sale.ticketTotal}
          comboTotal={sale.comboTotal}
          selectedCombos={sale.selectedCombos}
          grandTotal={sale.grandTotal}
          submitting={sale.submitting}
          handleCheckout={sale.handleCheckout}
          setStep={sale.setStep}
        />
      )}

      {sale.step === 6 && sale.completedOrder && (
        <SuccessStep
          completedOrder={sale.completedOrder}
          selectedMovie={sale.selectedMovie}
          selectedShowtime={sale.selectedShowtime}
          selectedDate={sale.selectedDate}
          customer={sale.customer}
          paymentMethod={sale.paymentMethod}
          paymentConfig={sale.paymentConfig}
          selectedSeats={sale.selectedSeats}
          selectedCombos={sale.selectedCombos}
          combos={sale.combos}
          grandTotal={sale.grandTotal}
          handleReset={sale.handleReset}
          navigate={navigate}
        />
      )}
    </div>
  );
}
