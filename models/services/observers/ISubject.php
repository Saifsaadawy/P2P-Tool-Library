<?php

interface ISubject
{
    public function attach(IObserver $observer): void;
    public function detach(IObserver $observer): void;
    public function notify(string $event, array $data): void;
}
