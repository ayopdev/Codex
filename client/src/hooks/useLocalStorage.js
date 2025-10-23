import { useEffect, useState } from 'react';

const isBrowser = () => typeof window !== 'undefined';

export const useLocalStorage = (key, initialValue) => {
  const readValue = () => {
    if (!isBrowser()) return initialValue;
    try {
      const item = window.localStorage.getItem(key);
      return item ? JSON.parse(item) : initialValue;
    } catch (error) {
      console.warn(`Unable to read localStorage key "${key}":`, error);
      return initialValue;
    }
  };

  const [storedValue, setStoredValue] = useState(readValue);

  useEffect(() => {
    setStoredValue(readValue());
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [key]);

  const setValue = (value) => {
    try {
      const valueToStore = value instanceof Function ? value(storedValue) : value;
      setStoredValue(valueToStore);
      if (isBrowser()) {
        window.localStorage.setItem(key, JSON.stringify(valueToStore));
      }
    } catch (error) {
      console.warn(`Unable to set localStorage key "${key}":`, error);
    }
  };

  const removeValue = () => {
    try {
      if (isBrowser()) {
        window.localStorage.removeItem(key);
      }
      setStoredValue(initialValue);
    } catch (error) {
      console.warn(`Unable to remove localStorage key "${key}":`, error);
    }
  };

  return [storedValue, setValue, removeValue];
};
