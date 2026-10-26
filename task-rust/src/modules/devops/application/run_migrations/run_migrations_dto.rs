use crate::modules::shared::application::abstract_input_dto::AbstractInputDto;
use crate::modules::shared::application::primitives::Primitives;

#[derive(Debug, Clone, Default)]
pub struct RunMigrationsDto {
    abstract_input_dto: AbstractInputDto,
}

extends!(RunMigrationsDto, abstract_input_dto, AbstractInputDto);

impl RunMigrationsDto {
    pub fn from_primitives(primitives: &Primitives) -> Self {
        Self { abstract_input_dto: AbstractInputDto::from_primitives(primitives) }
    }
}
