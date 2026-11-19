from abc import ABC, abstractmethod


class InterfaceMailer(ABC):

    @abstractmethod
    def send(self) -> None:
        ...
