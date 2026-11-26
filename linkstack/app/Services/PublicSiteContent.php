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

    /** Valores padrão anteriores; jamais sobrescrever textos editados pelo administrador. */
    private const LEGACY_DEFAULTS = [
        'hero_eyebrow' => 'Sua presença em um só lugar',
        'hero_title' => 'Um link.',
        'hero_accent' => 'Todos os seus caminhos.',
        'hero_description' => 'Reúna suas redes sociais, contatos e conteúdos em uma página com a sua cara. Compartilhe um único link e facilite a conexão com quem importa.',
        'hero_note' => 'Uma página para compartilhar onde você estiver.',
        'features_eyebrow' => 'Tudo começa com um link',
        'features_title' => 'Seu mundo, organizado do seu jeito',
        'features_intro' => 'Apresente o que você faz e ajude as pessoas a encontrarem seus conteúdos, produtos e canais de contato.',
        'feature1_title' => 'Seus links reunidos',
        'feature1_description' => 'Adicione seus destinos importantes em uma única página, pronta para compartilhar na bio e onde mais quiser.',
        'feature2_title' => 'Uma página com sua identidade',
        'feature2_description' => 'Escolha um tema e personalize a apresentação para criar uma página que combine com você.',
        'feature3_title' => 'Mais formas de se conectar',
        'feature3_description' => 'Organize links, textos e informações de contato para orientar seus visitantes.',
        'steps_eyebrow' => 'Simples de começar',
        'steps_title' => 'Da sua ideia para o mundo',
        'steps_intro' => 'Crie um ponto de encontro para sua presença digital e mantenha seus links organizados em um só lugar.',
        'step1_title' => 'Crie sua conta',
        'step1_description' => 'Comece pelo cadastro e acesse seu painel.',
        'step2_title' => 'Monte sua página',
        'step2_description' => 'Adicione seus links e personalize a apresentação.',
        'step3_title' => 'Compartilhe seu link',
        'step3_description' => 'Coloque sua página na bio, nas redes e onde seu público estiver.',
        'faq_eyebrow' => 'Perguntas frequentes',
        'faq_title' => 'Quer saber mais?',
        'faq1_question' => 'O que é uma árvore de links?',
        'faq1_answer' => 'É uma página que reúne vários links em um só endereço, facilitando o acesso aos seus conteúdos e contatos.',
        'faq2_question' => 'Posso personalizar minha página?',
        'faq2_answer' => 'Sim. No painel, você pode escolher um tema e organizar os links e informações da sua página.',
        'faq3_question' => 'Onde posso compartilhar minha página?',
        'faq3_answer' => 'Você pode compartilhar o endereço na bio das redes sociais, em mensagens ou em qualquer lugar que aceite links.',
        'final_eyebrow' => 'Pronto para começar?',
        'final_title' => 'Seu próximo clique começa aqui.',
        'final_description' => 'Reúna seus links e apresente tudo o que importa em uma página.',
    ];

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
                    'nav_catalog' => ['label' => 'Menu: Catálogo completo', 'default' => 'Todos os recursos', 'max' => 80],
                    'nav_roadmap' => ['label' => 'Menu: Novas ferramentas', 'default' => 'Próximas implementações', 'max' => 80],
                    'button_explore' => ['label' => 'Botão: Conhecer recursos', 'default' => 'Explorar recursos', 'max' => 80],
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
                    'hero_eyebrow' => ['label' => 'Chamada superior', 'default' => 'Sua presença digital, com mais possibilidades', 'max' => 120],
                    'hero_title' => ['label' => 'Título principal', 'default' => 'Seu conteúdo, seus contatos.', 'max' => 120],
                    'hero_accent' => ['label' => 'Título em destaque', 'default' => 'Um único lugar.', 'max' => 120],
                    'hero_description' => ['label' => 'Descrição principal', 'default' => 'Transforme sua bio em uma página que apresenta quem você é. Reúna seus links, formas de contato e conteúdos em blocos organizados, personalize o visual e acompanhe os cliques diretamente no painel.', 'max' => 1000, 'multiline' => true],
                    'hero_note' => ['label' => 'Mensagem curta abaixo dos botões', 'default' => 'Crie sua página, organize seus canais e compartilhe seu endereço.', 'max' => 240],
                    'hero_demo_label' => ['label' => 'Descrição acessível do exemplo', 'default' => 'Exemplo de página de links', 'max' => 150],
                ],
            ],
            'features' => [
                'title' => 'Seção Recursos',
                'description' => 'Cabeçalho e os três cards de apresentação de funcionalidades.',
                'fields' => [
                    'show_features' => ['label' => 'Exibir seção Recursos', 'default' => true, 'type' => 'checkbox'],
                    'features_eyebrow' => ['label' => 'Chamada', 'default' => 'Recursos para cada conexão', 'max' => 120],
                    'features_title' => ['label' => 'Título', 'default' => 'Uma página completa, organizada do seu jeito', 'max' => 160],
                    'features_intro' => ['label' => 'Descrição', 'default' => 'Do primeiro link ao acompanhamento de cliques: ferramentas para ajudar pessoas a encontrar seus canais, entender o que você oferece e chegar ao próximo passo.', 'max' => 700, 'multiline' => true],
                    'feature1_title' => ['label' => 'Card 1: título', 'default' => 'Links e blocos que fazem sentido', 'max' => 100],
                    'feature1_description' => ['label' => 'Card 1: descrição', 'default' => 'Organize plataformas, links personalizados, e-mail, telefone, vCard, títulos e textos para cada informação ter seu lugar.', 'max' => 600, 'multiline' => true],
                    'feature2_title' => ['label' => 'Card 2: título', 'default' => 'Identidade visual com liberdade', 'max' => 100],
                    'feature2_description' => ['label' => 'Card 2: descrição', 'default' => 'Escolha entre os temas disponíveis, ajuste fundos, imagens e ícones e apresente sua página com uma aparência que combine com você.', 'max' => 600, 'multiline' => true],
                    'feature3_title' => ['label' => 'Card 3: título', 'default' => 'Veja o que desperta interesse', 'max' => 100],
                    'feature3_description' => ['label' => 'Card 3: descrição', 'default' => 'Consulte cliques e seus links mais acessados no painel. Recursos analíticos adicionais podem depender do seu plano.', 'max' => 600, 'multiline' => true],
                ],
            ],
            'steps' => [
                'title' => 'Seção Como funciona',
                'description' => 'Introdução e três etapas da jornada do usuário.',
                'fields' => [
                    'show_steps' => ['label' => 'Exibir seção Como funciona', 'default' => true, 'type' => 'checkbox'],
                    'steps_eyebrow' => ['label' => 'Chamada', 'default' => 'Três passos para começar', 'max' => 120],
                    'steps_title' => ['label' => 'Título', 'default' => 'Do cadastro ao link compartilhado', 'max' => 160],
                    'steps_intro' => ['label' => 'Descrição', 'default' => 'Você organiza suas informações no painel e decide o que as pessoas encontram ao abrir sua página.', 'max' => 700, 'multiline' => true],
                    'step1_title' => ['label' => 'Etapa 1: título', 'default' => 'Escolha seu endereço', 'max' => 100],
                    'step1_description' => ['label' => 'Etapa 1: descrição', 'default' => 'Crie sua conta e defina o endereço público da sua página no LinkFree.', 'max' => 400],
                    'step2_title' => ['label' => 'Etapa 2: título', 'default' => 'Monte sua apresentação', 'max' => 100],
                    'step2_description' => ['label' => 'Etapa 2: descrição', 'default' => 'Adicione blocos de conteúdo, links de contato e escolha um tema disponível.', 'max' => 400],
                    'step3_title' => ['label' => 'Etapa 3: título', 'default' => 'Compartilhe e acompanhe', 'max' => 100],
                    'step3_description' => ['label' => 'Etapa 3: descrição', 'default' => 'Publique seu endereço onde quiser e use os cliques do painel para revisar seus destaques.', 'max' => 400],
                ],
            ],
            'faq' => [
                'title' => 'Perguntas frequentes',
                'description' => 'Título, perguntas e respostas exibidas ao visitante.',
                'fields' => [
                    'show_faq' => ['label' => 'Exibir perguntas frequentes', 'default' => true, 'type' => 'checkbox'],
                    'faq_eyebrow' => ['label' => 'Chamada', 'default' => 'Respostas diretas', 'max' => 120],
                    'faq_title' => ['label' => 'Título', 'default' => 'Tire suas dúvidas sobre o LinkFree', 'max' => 160],
                    'faq1_question' => ['label' => 'Pergunta 1', 'default' => 'Que conteúdos posso colocar na minha página?', 'max' => 160],
                    'faq1_answer' => ['label' => 'Resposta 1', 'default' => 'Você pode combinar links personalizados e plataformas com blocos de contato, vCard, títulos, textos e espaçadores, conforme os tipos instalados no sistema.', 'max' => 800, 'multiline' => true],
                    'faq2_question' => ['label' => 'Pergunta 2', 'default' => 'Posso alterar o visual depois de criar a página?', 'max' => 160],
                    'faq2_answer' => ['label' => 'Resposta 2', 'default' => 'Sim. Você pode reorganizar blocos, escolher um tema instalado e ajustar opções visuais liberadas para sua conta.', 'max' => 800, 'multiline' => true],
                    'faq3_question' => ['label' => 'Pergunta 3', 'default' => 'Como acompanho o desempenho dos meus links?', 'max' => 160],
                    'faq3_answer' => ['label' => 'Resposta 3', 'default' => 'O painel reúne totais de cliques e destaca seus links mais acessados. O relatório de origem dos visitantes é reservado a membros VIP e administradores.', 'max' => 800, 'multiline' => true],
                ],
            ],
            'final' => [
                'title' => 'Chamada final',
                'description' => 'Mensagem final que convida o visitante a criar uma conta.',
                'fields' => [
                    'show_final' => ['label' => 'Exibir chamada final', 'default' => true, 'type' => 'checkbox'],
                    'final_eyebrow' => ['label' => 'Chamada', 'default' => 'Seu próximo passo', 'max' => 120],
                    'final_title' => ['label' => 'Título', 'default' => 'Transforme sua presença em um caminho claro.', 'max' => 160],
                    'final_description' => ['label' => 'Descrição', 'default' => 'Apresente seu trabalho, organize seus links e ofereça aos visitantes uma forma simples de encontrar o que procuram.', 'max' => 800, 'multiline' => true],
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
            // Se a versão armazenada ainda usa exatamente o texto padrão antigo,
            // aplique o novo padrão em memória. Conteúdos personalizados não mudam
            // e o arquivo persistente nunca é regravado nesta leitura.
            foreach (self::LEGACY_DEFAULTS as $key => $priorDefault) {
                if (isset($stored[$key]) && $stored[$key] === $priorDefault) {
                    unset($stored[$key]);
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
