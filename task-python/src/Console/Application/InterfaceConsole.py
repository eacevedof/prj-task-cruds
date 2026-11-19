from abc import ABC, abstractmethod


class InterfaceConsole(ABC):

    @abstractmethod
    def run(self) -> None:
        ...
