// Countdown.js
import React, { useState, useEffect } from 'react';

// Función para formatear la diferencia en horas, minutos y segundos
const formatTimeLeft = (difference) => {
  if (difference <= 0) return null; // Si ya pasó, no mostramos nada

  const hours = Math.floor((difference / (1000 * 60 * 60)) % 24);
  const minutes = Math.floor((difference / (1000 * 60)) % 60);
  const seconds = Math.floor((difference / 1000) % 60);
  return `${hours}:${minutes}:${seconds}`;
};

const Countdown = ({ targetDate }) => {
  const [timeLeft, setTimeLeft] = useState(formatTimeLeft(new Date(targetDate) - new Date()));

  useEffect(() => {
    // Solo actualiza si la fecha es válida y no ha pasado
    const intervalId = setInterval(() => {
      const difference = new Date(targetDate) - new Date();
      const formattedTime = formatTimeLeft(difference);
      setTimeLeft(formattedTime);
    }, 1000);

    return () => clearInterval(intervalId); // Limpiar el intervalo cuando se desmonte el componente
  }, [targetDate]);

  return (
    <span>
      {timeLeft ? timeLeft : '00:00:00'}
    </span>
  );
};

export default Countdown;
