<?php

interface IObserver
{
    /**
     * @param string $event  e.g. 'reservation.approved', 'payment.completed'
     * @param array  $data   event payload
     */
    public function update(string $event, array $data): void;
}
