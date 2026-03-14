<?php
namespace Database\Seeders;

use App\Models\Efetivo;
use Illuminate\Database\Seeder;

class EfetivoSeeder extends Seeder
{
    public function run(): void
    {
        $efetivos = [
            ['saram' => '3047512', 'nome_guerra' => 'CASTILHO',        'nome_completo' => 'Diogo Silva CASTILHO',                          'posto' => 'Cel Av',          'email' => 'castilhodsc@fab.mil.br'],
            ['saram' => '1351370', 'nome_guerra' => 'PRATESI',         'nome_completo' => 'Antonio Luis Kostienkow PRATESI',               'posto' => 'Cel Av R/1',      'email' => 'pratesialkp@fab.mil.br'],
            ['saram' => '3257916', 'nome_guerra' => 'FONTES',          'nome_completo' => 'Pablo Rodrigues FONTES',                        'posto' => 'Ten Cel Av',      'email' => 'fontesprf@fab.mil.br'],
            ['saram' => '3324052', 'nome_guerra' => 'VALNECK',         'nome_completo' => 'VALNECK Peixoto de Oliveira Melo',              'posto' => 'Ten Cel Av',      'email' => 'valneckvpom@fab.mil.br'],
            ['saram' => '3686515', 'nome_guerra' => 'MATTOS BRITO',    'nome_completo' => 'FRANCISCO DE MATTOS BRITO JUNIOR',              'posto' => 'TEN CEL ENG',     'email' => 'mattosbritofmbj@fab.mil.br'],
            ['saram' => '3490351', 'nome_guerra' => 'CAPUCHINHO',      'nome_completo' => 'Thiago Romeiro CAPUCHINHO',                     'posto' => 'Maj Av',          'email' => 'capuchinhotrc@fab.mil.br'],
            ['saram' => '4111281', 'nome_guerra' => 'LACERDA',         'nome_completo' => 'Renan de LACERDA Lima Gonçalves',               'posto' => 'Maj Int',         'email' => 'lacerdarllg@fab.mil.br'],
            ['saram' => '6084966', 'nome_guerra' => 'THAIANE BENETTI', 'nome_completo' => 'THAIANE BENETTI CARVALHO DE OLIVEIRA VIEIRA',   'posto' => 'CAP INT',         'email' => 'thaianebenettitbcov@fab.mil.br'],
            ['saram' => '6123120', 'nome_guerra' => 'MACÊDO',          'nome_completo' => 'Rafael MACÊDO Trindade',                        'posto' => 'Cap Eng',         'email' => 'macedormt@fab.mil.br'],
            ['saram' => '6425216', 'nome_guerra' => 'HELANE',          'nome_completo' => 'HELANE Rosario da Cruz Nogueira',               'posto' => 'Cap Int',         'email' => 'helanehrcn@fab.mil.br'],
            ['saram' => '1673327', 'nome_guerra' => 'NELSON',          'nome_completo' => 'NELSON Rodrigues da Costa Filho',               'posto' => 'Cap QOEA ANV R/1','email' => 'nelsonnrcf@fab.mil.br'],
            ['saram' => '1645439', 'nome_guerra' => 'SANTI',           'nome_completo' => 'Leandro SANTI da Silva',                        'posto' => 'Cap QOEA SVA R/1','email' => 'santilss@fab.mil.br'],
            ['saram' => '1985736', 'nome_guerra' => 'MICHETTI',        'nome_completo' => 'Marcos Roberto MICHETTI',                       'posto' => 'Cap QOEA ANV R/1','email' => 'michettimrm@fab.mil.br'],
            ['saram' => '2603624', 'nome_guerra' => 'OLIVEIRA',        'nome_completo' => 'Robson de OLIVEIRA Parada',                     'posto' => 'Cap QOEA SUP R/1','email' => 'oliveirarop@fab.mil.br'],
            ['saram' => '3448703', 'nome_guerra' => 'MELO',            'nome_completo' => 'Thiago de MELO Rocha',                          'posto' => '1° Ten QOEA ANV', 'email' => 'melotmr@fab.mil.br'],
            ['saram' => '7391110', 'nome_guerra' => 'CATIANA FARIA',   'nome_completo' => 'CATIANA FARIA DOS SANTOS',                      'posto' => '1° TEN QOCON ADM','email' => 'catianacfs@fab.mil.br'],
            ['saram' => '7391188', 'nome_guerra' => 'MILITÃO',         'nome_completo' => 'ANGELA de Lima MILITÃO',                        'posto' => '1° Ten QOCon ADM','email' => 'angelamilitaoalm@fab.mil.br'],
            ['saram' => '7433794', 'nome_guerra' => 'TATIANA ROCHA',   'nome_completo' => 'TATIANA SOUSA DA ROCHA',                        'posto' => '1° TEN QOCON CCO','email' => 'tatianarochatsr@fab.mil.br'],
            ['saram' => '7432445', 'nome_guerra' => 'MARIANA RODRIGUES','nome_completo' => 'MARIANA RODRIGUES QUEIROZ MOREIRA',            'posto' => '1° TEN QOCON CCO','email' => 'mariana.rodrigues@gmail.com'],
            ['saram' => '3245926', 'nome_guerra' => 'FRANCO',          'nome_completo' => 'Gustavo Luiz FRANCO',                           'posto' => '1° Ten Esp Aer SUP','email' => 'francoglf@fab.mil.br'],
            ['saram' => '7534710', 'nome_guerra' => 'PRADO',           'nome_completo' => 'Matheus PRADO',                                 'posto' => '2° Ten QOCon PRU', 'email' => 'pradomp@fab.mil.br'],
            ['saram' => '7537301', 'nome_guerra' => 'ANA PRIANTE',     'nome_completo' => 'ANA CLÁUDIA APARECIDA PRIANTE',                 'posto' => '2° TEN QOCON CCO', 'email' => 'anaprianteacap@fab.mil.br'],
            ['saram' => '7623070', 'nome_guerra' => 'CARLA',           'nome_completo' => 'CARLA Pereira Machado Homem',                   'posto' => '2° Ten QOCon ADM', 'email' => 'carlacpmh@fab.mil.br'],
            ['saram' => '2714710', 'nome_guerra' => 'PROENÇA',         'nome_completo' => 'Rogério da Silva PROENÇA',                      'posto' => '2° Ten Esp Aer SUP','email' => 'proencarsp@fab.mil.br'],
            ['saram' => '3503186', 'nome_guerra' => 'ALEX SANDRO',     'nome_completo' => 'ALEX SANDRO SOUTO BARBOSA',                     'posto' => '2° TEN ESP AER ANV','email' => 'alexsandroassb@fab.mil.br'],
            ['saram' => '2086735', 'nome_guerra' => 'MARTINO',         'nome_completo' => 'Flávio de Souza MARTINO',                       'posto' => 'SO BMA',           'email' => 'martinofsm@fab.mil.br'],
            ['saram' => '2345560', 'nome_guerra' => 'LOBO',            'nome_completo' => 'Marcos Antonio Muniz LOBO',                     'posto' => 'SO BMA',           'email' => 'lobomaml@fab.mil.br'],
            ['saram' => '3372332', 'nome_guerra' => 'SILVIA',          'nome_completo' => 'SILVIA Soares Ferreira Gonçalves',              'posto' => 'SO SAD',           'email' => 'silviassfg@fab.mil.br'],
            ['saram' => '2961849', 'nome_guerra' => 'CLEI',            'nome_completo' => 'Gilson CLEI José Barreto',                      'posto' => 'SO SAD',           'email' => 'cleigcjb@fab.mil.br'],
            ['saram' => '3288536', 'nome_guerra' => 'MICHEL',          'nome_completo' => 'MICHEL da Silva Soares',                        'posto' => 'SO BMA',           'email' => 'michelmss@fab.mil.br'],
            ['saram' => '2818477', 'nome_guerra' => 'ANDEILTON',       'nome_completo' => 'ANDEILTON Gomes de Souza',                      'posto' => 'SO BMA',           'email' => 'andeiltonags@fab.mil.br'],
            ['saram' => '3381218', 'nome_guerra' => 'ESTRELA',         'nome_completo' => 'Filipe ESTRELA Nunes',                          'posto' => 'SO BET',           'email' => 'estrelafen@fab.mil.br'],
            ['saram' => '2946521', 'nome_guerra' => 'CARLOS',          'nome_completo' => 'João CARLOS da Silva Pinto',                    'posto' => 'SO BMA',           'email' => 'carlosjcsp@fab.mil.br'],
            ['saram' => '3455378', 'nome_guerra' => 'RONALD',          'nome_completo' => 'BRUNO RONALD DA SILVA',                         'posto' => 'SO SAD',           'email' => 'ronaldbrs@fab.mil.br'],
            ['saram' => '4069323', 'nome_guerra' => 'DARIELE',         'nome_completo' => 'DARIELE Elisa Reis Breginski',                  'posto' => 'SO BET',           'email' => 'darielederb@fab.mil.br'],
            ['saram' => '3210685', 'nome_guerra' => 'RUBIM',           'nome_completo' => 'Anderson RUBIM Musi Dias',                      'posto' => 'SO SAD',           'email' => 'rubimarmd@fab.mil.br'],
            ['saram' => '4039769', 'nome_guerra' => 'QUINTELA',        'nome_completo' => 'Raquel QUINTELA Gomes do Nascimento',           'posto' => 'SO SAD',           'email' => 'quintelarqgn@fab.mil.br'],
            ['saram' => '3034968', 'nome_guerra' => 'BEMFICA',         'nome_completo' => 'André da Silva BEMFICA',                        'posto' => 'SO SAD',           'email' => 'bemficaasb@fab.mil.br'],
            ['saram' => '0621714', 'nome_guerra' => 'JESUS',           'nome_completo' => 'Hélio Marcos de JESUS',                         'posto' => 'SO BSP Refm',      'email' => 'jesushmj@fab.mil.br'],
            ['saram' => '2709988', 'nome_guerra' => 'MOISES',          'nome_completo' => 'MOISES Ferreira da Silva',                      'posto' => '1S BMA',           'email' => 'moisesmfs@fab.mil.br'],
            ['saram' => '3341704', 'nome_guerra' => 'ADEMIR',          'nome_completo' => 'ADEMIR Aparecido de Freitas',                   'posto' => '1S BMB',           'email' => 'ademiraaf@fab.mil.br'],
            ['saram' => '4279565', 'nome_guerra' => 'TREVISAN',        'nome_completo' => 'Euclides Jorge TREVISAN Filho',                 'posto' => '1S BMA',           'email' => 'trevisanejtf@fab.mil.br'],
            ['saram' => '3463907', 'nome_guerra' => 'BRASIL',          'nome_completo' => 'Vagner de Oliveira BRASIL',                     'posto' => '1S BSP',           'email' => 'brasilvob@fab.mil.br'],
            ['saram' => '4360389', 'nome_guerra' => 'GISELE SILVA',    'nome_completo' => 'GISELE SILVA ODILON',                           'posto' => '1S BSP',           'email' => 'giselesilvagso@fab.mil.br'],
            ['saram' => '3467317', 'nome_guerra' => 'LIMA',            'nome_completo' => 'Marcelo LIMA da Silva',                         'posto' => '1S SAD',           'email' => 'limamls@fab.mil.br'],
            ['saram' => '4112695', 'nome_guerra' => 'FERNANDO',        'nome_completo' => 'FERNANDO dos Santos Souza',                     'posto' => '1S BMB',           'email' => 'fernandofss@fab.mil.br'],
            ['saram' => '6323847', 'nome_guerra' => 'BRUM',            'nome_completo' => 'Eric Tiago Zuchi de Andrade BRUM',              'posto' => '2S BMA',           'email' => 'brumetzab@fab.mil.br'],
            ['saram' => '4157940', 'nome_guerra' => 'AMADOR',          'nome_completo' => 'Maicon Fonseca AMADOR',                         'posto' => '2S SAD',           'email' => 'amadormfa@fab.mil.br'],
            ['saram' => '6329969', 'nome_guerra' => 'DOLFINI',         'nome_completo' => 'Gustavo Rosa DOLFINI',                          'posto' => '2S BMA',           'email' => 'dolfinigrd@fab.mil.br'],
            ['saram' => '6255620', 'nome_guerra' => 'ANDRESSA COSTA',  'nome_completo' => 'ANDRESSA XAVIER DA COSTA',                      'posto' => '2S SIN',           'email' => 'andressacostaaxc@fab.mil.br'],
        ];

        foreach ($efetivos as $e) {
            Efetivo::firstOrCreate(
                ['saram' => $e['saram']],
                array_merge($e, ['om_origem' => 'GAC-PAC', 'ativo' => true, 'oculto' => false])
            );
        }
    }
}
