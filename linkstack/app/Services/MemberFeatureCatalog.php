<?php

namespace App\Services;

/**
 * Curadoria pública baseada em funcionalidades verificadas no código.
 * Não concede permissões nem substitui a checagem de acesso dos controllers.
 */
final class MemberFeatureCatalog
{
    public static function groups(): array
    {
        $groups = [
            [
                'id' => 'identidade',
                'eyebrow' => 'Identidade digital',
                'title' => 'Uma página que apresenta você',
                'description' => 'Transforme seu endereço público em um ponto de encontro para sua audiência.',
                'items' => [
                    ['title' => 'Página com endereço próprio no LinkFree', 'description' => 'Reúna seus destinos em um perfil com nome, descrição e endereço público compartilhável.'],
                    ['title' => 'Imagem e apresentação pessoal', 'description' => 'Adicione imagem de perfil, descrição e uma apresentação alinhada à sua identidade.'],
                    ['title' => 'Presença em buscas e compartilhamentos', 'description' => 'Configure a preferência de indexação, o slogan de compartilhamento e a exibição do contador público de visitas.'],
                ],
            ],
            [
                'id' => 'blocos',
                'eyebrow' => 'Conteúdo e conversas',
                'title' => 'Muito mais do que uma lista de links',
                'description' => 'Escolha o formato certo para cada informação e ofereça caminhos claros para seu público.',
                'items' => [
                    ['title' => 'Links e plataformas', 'description' => 'Use endereços personalizados ou selecione plataformas predefinidas com botões e identidade visual da marca.'],
                    ['title' => 'Contato direto', 'description' => 'Adicione e-mail, telefone e cartão de contato vCard quando o tipo de bloco estiver disponível.'],
                    ['title' => 'Estrutura visual flexível', 'description' => 'Organize a leitura com títulos, textos e espaçadores, além de outros tipos instalados.'],
                    ['title' => 'Organização sob seu controle', 'description' => 'Adicione, edite, reordene e remova blocos, respeitando os limites da sua conta.'],
                    ['title' => 'Aparência dos botões', 'description' => 'Edite os estilos oferecidos para seus botões e personalize ícones da página.'],
                ],
            ],
            [
                'id' => 'visual',
                'eyebrow' => 'Personalização',
                'title' => 'Sua identidade em cada detalhe',
                'description' => 'Escolha como os visitantes verão sua página em dispositivos diferentes.',
                'items' => [
                    ['title' => 'Temas e pré-visualização', 'description' => 'Explore os temas instalados e veja a apresentação antes de aplicar.'],
                    ['title' => 'Fundos personalizados', 'description' => 'Configure o fundo da página e as opções de tema disponibilizadas pelo administrador.'],
                    ['title' => 'Ícones visuais', 'description' => 'Ajuste os ícones usados na página para destacar seus destinos.'],
                ],
            ],
            [
                'id' => 'resultados',
                'eyebrow' => 'Acompanhamento',
                'title' => 'Descubra quais links recebem atenção',
                'description' => 'Indicadores claros para revisar a organização e o destaque dos seus conteúdos.',
                'items' => [
                    ['title' => 'Visão geral', 'description' => 'Acompanhe a quantidade de links e o total de cliques exibidos no painel.'],
                    ['title' => 'Compartilhamento por QR Code', 'description' => 'Abra o QR Code do perfil no painel para facilitar o acesso à sua página. O download em PNG depende do suporte disponível na instalação.', 'note' => 'QR Code da própria página.'],
                    ['title' => 'Links mais acessados', 'description' => 'Identifique os destinos com mais cliques no resumo dos seus links.'],
                    ['title' => 'Origem dos visitantes', 'description' => 'Consulte um resumo das fontes de visita dos últimos 30 dias.', 'note' => 'Disponível para VIP e administrador.'],
                ],
            ],
            [
                'id' => 'crescimento',
                'eyebrow' => 'Confiança e comunidade',
                'title' => 'Fortaleça as conexões com seu público',
                'description' => 'Recursos para divulgar sua página e organizar sua presença dentro do LinkFree.',
                'items' => [
                    ['title' => 'Indicações por convite', 'description' => 'Crie um link de convite, compartilhe-o e acompanhe cadastros atribuídos.', 'note' => 'Não há recompensa financeira automática.'],
                    ['title' => 'Selo de verificação', 'description' => 'Solicite a análise do administrador; depois da aprovação, controle a exibição do selo no perfil.', 'note' => 'A concessão depende de aprovação.'],
                ],
            ],
            [
                'id' => 'controle',
                'eyebrow' => 'Autonomia',
                'title' => 'Gerencie a conta com clareza',
                'description' => 'Mantenha seus dados e o conteúdo da página sob seus próprios controles.',
                'items' => [
                    ['title' => 'Informações da conta', 'description' => 'Atualize seus dados de acesso e utilize os controles disponíveis de segurança e exclusão de conta.'],
                    ['title' => 'Denúncia de conteúdo', 'description' => 'Utilize o canal público de denúncias para encaminhar conteúdo impróprio para revisão.'],
                ],
            ],
        ];
        if (filter_var(env('ALLOW_USER_EXPORT'), FILTER_VALIDATE_BOOLEAN)) {
            $groups[5]['items'][] = [
                'title' => 'Exportação de informações',
                'description' => 'Baixe seus links e os dados exportáveis da conta pelo painel, quando o recurso estiver habilitado.',
            ];
        }
        if (filter_var(env('ALLOW_USER_IMPORT'), FILTER_VALIDATE_BOOLEAN)) {
            $groups[5]['items'][] = [
                'title' => 'Importação de dados',
                'description' => 'Importe dados compatíveis para o painel, quando a configuração do site permitir.',
            ];
        }
        return $groups;
    }

    /** Somente ferramentas novas efetivamente citadas e ainda não entregues. */
    public static function upcoming(): array
    {
        $items = [];
        // PR #38: o asset não existe na main auditada; quando publicado na
        // aplicação, o anúncio deixa de tratar a ferramenta como futura.
        if (!is_file(base_path('assets/js/linkfree-platform-picker.js'))) {
            $items[] = [
                'title' => 'Pré-visualização interativa de botões por plataforma',
                'description' => 'Explore a aparência dos botões, veja o ícone e os estilos correspondentes e confirme a plataforma apenas depois de visualizar a prévia.',
                'status' => 'Em validação',
                'note' => 'Funcionalidade em revisão; disponibilidade e data de lançamento ainda não confirmadas.',
            ];
        }
        return $items;
    }
}
