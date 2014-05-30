<?php

namespace Owkkuri\SpeedtestBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

use Owkkuri\SpeedtestBundle\Entity\Server;

class LoadServersCommand extends ContainerAwareCommand
{
    private $useragent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13';

    protected function configure()
    {
        $this->setName('speedtest:load:servers')
            ->setDescription('Load speedtest servers into the database')
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Force download of new servers list, even if local copy exists.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $serversUrl = 'http://c.speedtest.net/speedtest-servers-static.php';
        $basePath = '@OwkkuriSpeedtestBundle/Resources/xml/';
        $filename = 'servers.xml';
        $fullpath = $basePath . $filename;
        $fs = new Filesystem();
        /* @var $kernel \AppKernel */
        $kernel = $this->getContainer()->get('kernel');

        try {
            $path = $kernel->locateResource($fullpath);
        } catch (\InvalidArgumentException $e) {
            $output->writeln('Couldn\'t get file.');
            $fs->touch($kernel->locateResource($basePath) . $filename);
            $path = $kernel->locateResource($fullpath);
        }

        if ($input->getOption('force') || filesize($path) < 100) {


            $fp = fopen($path, 'w');
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $serversUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_USERAGENT, $this->useragent);
            $contents = curl_exec($ch);
            $info = curl_getinfo($ch);

            $output->writeln("Got list: " . round($info['size_download'] / pow(1024, 1), 2) . "KB");

            curl_close($ch);
            fclose($fp);


            try {
                $fs->dumpFile($path, $contents);
            } catch
            (IOExceptionInterface $e) {
                $output->writeln('Error writing to ' . $e->getPath());
            }

        }

        $xml = simplexml_load_string(file_get_contents($path));

        $servers = $xml->xpath('/settings/servers/server');

        $em = $this->getContainer()->get('doctrine')->getEntityManager();

        $allowedKeys = array("url", "lat", "lon", "name", "cc", "country", "sponsor", "host", "url2");

        $repository = $this->getContainer()->get('doctrine')->getRepository('OwkkuriSpeedtestBundle:Server');
        foreach ($servers as $server) {

            $att = $server->attributes()->{'id'};

            $serverEntity = $repository->findOneByServerId((string)$att);


            if (!$serverEntity) {
                $serverEntity = new Server();
            }

            foreach ($server->attributes() as $key => $val) {
                /* @var $val \SimpleXMLElement */
                if ($key == 'id') {
                    $serverEntity->setServerId((string)$val);
                    continue;
                }

                if (!in_array(strtolower($key), $allowedKeys)) {
                    continue;
                }

                $serverEntity->{'set' . ucfirst($key)}((string)$val);
            }
            $em->persist($serverEntity);
        }
        $em->flush();

        $output->writeln($path);

    }
}