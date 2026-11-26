<?php

namespace App\Services;

use RuntimeException;

/**
 * Conteudos globais da landing page. Nao altera perfis individuais dos membros.
 * O arquivo permanece em storage/app, preservado pelo deploy seletivo.
 */
final class PublicSiteContent
{
    private const FILE = 'linkfree/public-site.json';
    private static ?array $cache = null;

    public static function sections(): array
    {
        return [
            'navigation' => [
                'title' => 'Navegação e botões',
                'description' => 'Textos da barra superior e ações de acesso; os destinos internos permanecem protegidos.',
                'fields' => [
                    'nav_features' => ['label' => 'Menu: Recursos', 'default' => 'Recursos', 'max' => 80],
                    'nav_steps' => ['label' => 'Menu: Como funciona', 'default' => 'Como funciona', 'max' => 80],
                    'nav_faq' => ['label' => 'Menu: Dúvidas', 'default' => 'Dúvidas', 'max' => 80],
                    'button_login' => ['label' => 'Botão: Entrar', 'default' => 'Entrar', 'max' => 80],
                    'button_register' => ['label' => 'Botão: Cadastro', 'default' => 'Criar minha página', 'max' => 80],
                    'button_dashboard' => ['label' => 'Botão: Painel', 'default' => 'Acessar meu painel', 'max' => 80],
                    'button_my_panel' => ['label' => 'Botão do menu: Meu painel', 'default' => 'Meu painel', 'max' => 80],
                    'button_learn' => ['label' => 'Botão secundário', 'default' => 'Como funciona', 'max' => 80],
                ],
            ],
            'hero' => [
                'title' => 'Apresentação inicial',
                'description' => 'Destaque, introdução e breve mensagem da página inicial.',
                'fields' => [
                    'hero_eyebrow' => ['label' => 'Chamada superior', 'default' => 'Sua presença digital em um só lugar', 'max' => 120],
                    'hero_title' => ['label' => 'Título principal', 'default' => 'Conecte seu público.', 'max' => 120],
                    'hero_accent' => ['label' => 'Título em destaque', 'default' => 'Fortaleça sua presença.', 'max' => 120],
                    'hero_description' => ['label' => 'Descrição principal', 'default' => 'Crie uma página pública personalizada para reunir seus links, conteúdos e canais de contato. Organize o que importa, escolha uma identidade visual e compartilhe um endereço que facilita cada conexão.', 'max' => 1000, 'multiline' => true],
                    'hero_note' => ['label' => 'Mensagem curta abaixo dos botões', 'default' => 'Uma forma mais clara de apresentar seu conteúdo, no seu ritmo e conforme os recursos do seu plano.', 'max' => 240],
                    'hero_demo_label' => ['label' => 'Descrição acessível do exemplo', 'default' => 'Exemplo de página de links', 'max' => 150],
                ],
            ],
            'features' => [
                'title' => 'Seção Recursos',
                'description' => 'Cabeçalho e os três cards de apresentação de funcionalidades.',
                'fields' => [
                    'show_features' => ['label' => 'Exibir seção Recursos', 'default' => true, 'type' => 'checkbox'],
                    'features_eyebrow' => ['label' => 'Chamada', 'default' => 'Muito mais que uma lista de links', 'max' => 120],
                    'features_title' => ['label' => 'Título', 'default' => 'O lugar certo para apresentar tudo o que você faz', 'max' => 160],
                    'features_intro' => ['label' => 'Descrição', 'default' => 'Transforme sua bio em um ponto de encontro: organize seus principais destinos, apresente sua identidade e torne o próximo clique mais simples.', 'max' => 700, 'multiline' => true],
                    'feature1_title' => ['label' => 'Card 1: título', 'default' => 'Todos os seus caminhos em uma página', 'max' => 100],
                    'feature1_description' => ['label' => 'Card 1: descrição', 'default' => 'Reúna links, canais de contato e conteúdos em um endereço público. Organize a ordem de exibição e mantenha tudo atualizado pelo painel.', 'max' => 600, 'multiline' => true],
                    'feature2_title' => ['label' => 'Card 2: título', 'default' => 'Sua marca em cada detalhe', 'max' => 100],
                    'feature2_description' => ['label' => 'Card 2: descrição', 'default' => 'Escolha entre os temas disponíveis, ajuste elementos visuais e construa uma apresentação coerente com sua identidade.', 'max' => 600, 'multiline' => true],
                    'feature3_title' => ['label' => 'Card 3: título', 'default' => 'Blocos que orientam o visitante', 'max' => 100],
                    'feature3_description' => ['label' => 'Card 3: descrição', 'default' => 'Vá além dos botões: combine links e tipos de blocos habilitados para apresentar informações e facilitar o caminho do seu público.', 'max' => 600, 'multiline' => true],
                ],
            ],
            'steps' => [
                'title' => 'Seção Como funciona',
                'description' => 'Introdução e três etapas da jornada do usuário.',
                'fields' => [
                    'show_steps' => ['label' => 'Exibir seção Como funciona', 'default' => true, 'type' => 'checkbox'],
                    'steps_eyebrow' => ['label' => 'Chamada', 'default' => 'Da ideia à sua página', 'max' => 120],
                    'steps_title' => ['label' => 'Título', 'default' => 'Crie, personalize e compartilhe', 'max' => 160],
                    'steps_intro' => ['label' => 'Descrição', 'default' => 'Uma jornada prática para dar mais clareza à sua presença online, sem depender de várias páginas espalhadas.', 'max' => 700, 'multiline' => true],
                    'step1_title' => ['label' => 'Etapa 1: título', 'default' => 'Comece com sua página', 'max' => 100],
                    'step1_description' => ['label' => 'Etapa 1: descrição', 'default' => 'Cadastre-se, conforme a disponibilidade da plataforma, e acesse seu espaço de edição.', 'max' => 400],
                    'step2_title' => ['label' => 'Etapa 2: título', 'default' => 'Organize e personalize', 'max' => 100],
                    'step2_description' => ['label' => 'Etapa 2: descrição', 'default' => 'Adicione links e blocos, escolha sua identidade visual e revise a apresentação.', 'max' => 400],
                    'step3_title' => ['label' => 'Etapa 3: título', 'default' => 'Divulgue com clareza', 'max' => 100],
                    'step3_description' => ['label' => 'Etapa 3: descrição', 'default' => 'Compartilhe o endereço da sua página em biografias, mensagens e outros canais.', 'max' => 400],
                ],
            ],
            'faq' => [
                'title' => 'Perguntas frequentes',
                'description' => 'Título, perguntas e respostas exibidas ao visitante.',
                'fields' => [
                    'show_faq' => ['label' => 'Exibir perguntas frequentes', 'default' => true, 'type' => 'checkbox'],
                    'faq_eyebrow' => ['label' => 'Chamada', 'default' => 'Perguntas frequentes', 'max' => 120],
                    'faq_title' => ['label' => 'Título', 'default' => 'O essencial antes de começar', 'max' => 160],
                    'faq1_question' => ['label' => 'Pergunta 1', 'default' => 'O que posso reunir em minha página?', 'max' => 160],
                    'faq1_answer' => ['label' => 'Resposta 1', 'default' => 'Links, plataformas, contatos e informações sobre você, organizados em um endereço público. A disponibilidade de tipos de bloco depende da configuração do sistema.', 'max' => 800, 'multiline' => true],
                    'faq2_question' => ['label' => 'Pergunta 2', 'default' => 'Como posso personalizar a apresentação?', 'max' => 160],
                    'faq2_answer' => ['label' => 'Resposta 2', 'default' => 'Você pode escolher temas disponíveis, organizar os links e editar informações do perfil. Alguns ajustes visuais dependem do seu plano e das opções ativadas.', 'max' => 800, 'multiline' => true],
                    'faq3_question' => ['label' => 'Pergunta 3', 'default' => 'Quais recursos dependem do plano?', 'max' => 160],
                    'faq3_answer' => ['label' => 'Resposta 3', 'default' => 'O relatório de origem de visitantes é reservado a contas VIP. Outros recursos podem depender de configuração; consulte a seção Recursos para conhecer as possibilidades.', 'max' => 800, 'multiline' => true],
                ],
            ],
            'final' => [
                'title' => 'Chamada final',
                'description' => 'Mensagem final que convida o visitante a criar uma conta.',
                'fields' => [
                    'show_final' => ['label' => 'Exibir chamada final', 'default' => true, 'type' => 'checkbox'],
                    'final_eyebrow' => ['label' => 'Chamada', 'default' => 'Sua presença merece clareza', 'max' => 120],
                    'final_title' => ['label' => 'Título', 'default' => 'Uma página. Novas possibilidades de conexão.', 'max' => 160],
                    'final_description' => ['label' => 'Descrição', 'default' => 'Faça dos seus links uma apresentação organizada, com identidade própria e caminhos simples para seu público seguir.', 'max' => 800, 'multiline' => true],
                ],
            ],
            'footer' => [
                'title' => 'Textos dos links do rodapé',
                'description' => 'Rótulos dos links públicos; ativação dos links e conteúdo legal continuam nas configurações existentes.',
                'fields' => [
                    'footer_home' => ['label' => 'Texto do link: Início', 'default' => 'Início', 'max' => 80],
                    'footer_terms' => ['label' => 'Texto do link: Termos', 'default' => 'Termos', 'max' => 80],
                    'footer_privacy' => ['label' => 'Texto do link: Privacidade', 'default' => 'Privacidade', 'max' => 80],
                    'footer_contact' => ['label' => 'Texto do link: Contato', 'default' => 'Contato', 'max' => 80],
                ],
            ],
        ];
    }

    private static function defaults(): array
    {
        $values = [];
        foreach (self::sections() as $section) {
            foreach ($section['fields'] as $key => $definition) {
                $values[$key] = $definition['default'];
            }
        }
        return $values;
    }

    private static function filePath(): string
    {
        return storage_path('app/'.self::FILE);
    }

    public static function values(): array
    {
        if (self::$cache === null) {
            $stored = [];
            $path = self::filePath();
            if (is_file($path)) {
                $decoded = json_decode((string) file_get_contents($path), true);
                if (is_array($decoded)) {
                    $stored = $decoded;
                }
            }
            self::$cache = array_replace(self::defaults(), array_intersect_key($stored, self::defaults()));
        }
        return self::$cache;
    }

    public static function text(string $key): string
    {
        $value = self::values()[$key] ?? '';
        return is_string($value) ? $value : '';
    }

    public static function enabled(string $section): bool
    {
        $key = 'show_'.$section;
        return (bool) (self::values()[$key] ?? false);
    }

    public static function saveSection(string $section, array $values): void
    {
        $sections = self::sections();
        if (!isset($sections[$section])) {
            throw new RuntimeException('Seção de personalização desconhecida.');
        }
        $allowed = array_keys($sections[$section]['fields']);
        if (count(array_diff(array_keys($values), $allowed)) > 0) {
            throw new RuntimeException('Campo de personalização não permitido.');
        }

        $path = self::filePath();
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new RuntimeException('Não foi possível criar a pasta de configuração.');
        }
        $lock = fopen($path.'.lock', 'c');
        if ($lock === false) {
            throw new RuntimeException('Não foi possível bloquear a configuração.');
        }
        try {
            if (!flock($lock, LOCK_EX)) {
                throw new RuntimeException('Não foi possível obter o bloqueio da configuração.');
            }
            $current = [];
            if (is_file($path)) {
                $decoded = json_decode((string) file_get_contents($path), true);
                if (is_array($decoded)) $current = $decoded;
            }
            $updated = array_replace($current, $values);
            $encoded = json_encode($updated, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
            $temp = tempnam($directory, '.lf-site-');
            if ($temp === false) throw new RuntimeException('Falha ao preparar arquivo temporário.');
            try {
                if (file_put_contents($temp, $encoded, LOCK_EX) === false) {
                    throw new RuntimeException('Falha ao salvar conteúdo.');
                }
                chmod($temp, 0640);
                if (!rename($temp, $path)) {
                    throw new RuntimeException('Falha ao concluir a gravação.');
                }
            } finally {
                if (is_file($temp)) unlink($temp);
            }
            self::$cache = null;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
