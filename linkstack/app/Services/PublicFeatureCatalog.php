<?php

namespace App\Services;

/**
 * Public, manually verified capabilities. Never expose private member data.
 *
 * Keep this list synchronized with authenticated routes and feature flags.
 * Future product ideas are excluded until approved AND absent from production.
 */
final class PublicFeatureCatalog
{
    public static function groups(): array
    {
        return [
            [
                'id' => 'identidade',
                'eyebrow' => 'Sua identidade',
                'title' => 'Uma presença digital com a sua assinatura',
                'description' => 'Apresente quem você é, o que faz e onde seu público pode encontrar você.',
                'items' => [
                    ['title' => 'Página pública personalizada', 'description' => 'Reúna suas informações e links em um endereço para compartilhar na bio, em mensagens ou onde preferir.'],
                    ['title' => 'Foto, biografia e informações', 'description' => 'Mantenha sua apresentação atualizada com imagem de perfil, descrição e dados públicos que decidir divulgar.'],
                    ['title' => 'Ícones de redes e contatos', 'description' => 'Facilite o acesso aos seus principais canais e organize sua presença em diferentes plataformas.'],
                ],
            ],
            [
                'id' => 'conteudo',
                'eyebrow' => 'Conteúdo organizado',
                'title' => 'Os links certos, na ordem que faz sentido',
                'description' => 'Transforme a página em um ponto de encontro para conteúdo, contatos e oportunidades.',
                'items' => [
                    ['title' => 'Links e blocos', 'description' => 'Adicione, edite, organize e remova links pelo painel. Crie a estrutura da sua página com os modelos de bloco disponíveis.'],
                    ['title' => 'Plataformas e botões', 'description' => 'Escolha destinos conhecidos e apresente suas opções com ícones e botões associados aos estilos existentes.'],
                    ['title' => 'Textos, títulos e espaçamentos', 'description' => 'Use os tipos de bloco habilitados para dar contexto e criar uma navegação mais clara entre seus conteúdos.'],
                    ['title' => 'Pré-visualização', 'description' => 'Confira a apresentação da sua página e os exemplos de blocos antes de continuar a organização.'],
                ],
            ],
            [
                'id' => 'visual',
                'eyebrow' => 'Identidade visual',
                'title' => 'Uma experiência que combina com sua marca',
                'description' => 'A aparência também faz parte da mensagem que você transmite.',
                'items' => [
                    ['title' => 'Temas e aparência', 'description' => 'Explore os temas disponíveis e ajuste a apresentação da página ao seu estilo.'],
                    ['title' => 'Fundos e opções visuais', 'description' => 'Personalize elementos visuais quando essas opções estiverem habilitadas na sua conta.'],
                    ['title' => 'Editor de botões', 'description' => 'Ajuste estilos de botões próprios quando a ferramenta estiver liberada na instalação.'],
                ],
            ],
            [
                'id' => 'alcance',
                'eyebrow' => 'Compartilhar e acompanhar',
                'title' => 'Mais clareza sobre sua presença online',
                'description' => 'Compartilhe seus destinos e acompanhe os dados disponibilizados para o seu perfil.',
                'items' => [
                    ['title' => 'Link e QR Code', 'description' => 'Divulgue o endereço do seu perfil, use as ações de compartilhamento e visualize o QR Code da página quando a geração estiver disponível.'],
                    ['title' => 'Cliques no painel', 'description' => 'Acompanhe indicadores de interação com seus links apresentados no painel de membros.'],
                    ['title' => 'Origem das visitas', 'description' => 'Em contas VIP, consulte o relatório de fontes de visitantes disponibilizado pelo sistema.', 'note' => 'Recurso VIP'],
                ],
            ],
            [
                'id' => 'conexoes',
                'eyebrow' => 'Conexão e confiança',
                'title' => 'Recursos que apoiam sua comunidade',
                'description' => 'Convide outras pessoas e cuide da forma como sua identidade aparece na plataforma.',
                'items' => [
                    ['title' => 'Convites e indicações', 'description' => 'Crie um link de convite e acompanhe cadastros atribuídos a ele. Não há recompensa financeira automática.'],
                    ['title' => 'Selo verificado', 'description' => 'Solicite avaliação administrativa, acompanhe o resultado e, após aprovação, escolha se deseja exibir o selo.'],
                    ['title' => 'Gestão da conta', 'description' => 'Atualize dados de acesso e utilize os controles disponíveis para sua conta.'],
                    ['title' => 'Portabilidade condicionada', 'description' => 'Exportação e importação podem estar disponíveis quando habilitadas nas configurações da plataforma.', 'note' => 'Conforme configuração'],
                ],
            ],
        ];
    }

    /**
     * Only future tools explicitly approved, not yet implemented and with a
     * confirmed scope belong here. There are currently no verified entries.
     */
    public static function upcoming(): array
    {
        return [];
    }
}
