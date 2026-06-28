<?php

namespace App\Tests\Functional;

use App\Entity\DemandeInscription;
use App\Repository\ProfessionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EmailTest extends WebTestCase
{
    public function testInscriptionRedirigeVersLoginSansEmail(): void
    {
        $client = static::createClient();
        $profession = static::getContainer()->get(ProfessionRepository::class)->findOneBy([]);

        $crawler = $client->request('GET', '/inscription');
        $form = $crawler->selectButton('Envoyer ma demande')->form([
            'inscription[prenom]' => 'Nouveau',
            'inscription[nom]' => 'Testeur',
            'inscription[email]' => 'email-test-'.uniqid().'@test.fr',
            'inscription[profession]' => $profession->getId(),
            'inscription[plainPassword][first]' => 'MotDePasse1234!',
            'inscription[plainPassword][second]' => 'MotDePasse1234!',
        ]);
        $client->submit($form);

        // La vérification email est désactivée : redirection directe vers /login, aucun email envoyé.
        $this->assertResponseStatusCodeSame(303);
        $this->assertResponseRedirects('/login');
        $this->assertEmailCount(0);
    }

    public function testApprobationDemandeEnvoieEmail(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@test.fr']);
        $client->loginUser($admin);

        $demande = $this->creerDemandePendante();

        // On doit d'abord visiter la page index pour que le token CSRF soit chargé en session,
        // puis filtrer le formulaire d'approbation par son action (une action par demande)
        $crawler = $client->request('GET', '/admin/demandes');
        $form = $crawler->filter('form[action$="/'.$demande->getId().'/approuver"]')->form();
        $client->submit($form);

        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        $this->assertEmailAddressContains($email, 'To', $demande->getEmail());
        $this->assertEmailSubjectContains($email, 'approuvé');
    }

    public function testRefusDemandeEnvoieEmail(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@test.fr']);
        $client->loginUser($admin);

        $demande = $this->creerDemandePendante();

        $crawler = $client->request('GET', '/admin/demandes/'.$demande->getId().'/refuser');
        $form = $crawler->selectButton('Confirmer le refus')->form([
            'refuser_demande[motifRejet]' => 'Profession non éligible pour l\'accès.',
        ]);
        $client->submit($form);

        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        $this->assertEmailAddressContains($email, 'To', $demande->getEmail());
        $this->assertEmailHtmlBodyContains($email, 'Profession non éligible');
    }

    private function creerDemandePendante(): DemandeInscription
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $profession = static::getContainer()->get(ProfessionRepository::class)->findOneBy([]);

        $demande = new DemandeInscription();
        // Email unique par uniqid() : évite les conflits d'unicité entre runs de tests
        $demande->setEmail('demande-'.uniqid().'@test.fr');
        $demande->setPrenom('Jean');
        $demande->setNom('Dupont');
        $demande->setProfession($profession);
        // Hash factice : le test ne vérifie pas le mot de passe, juste l'envoi d'email
        $demande->setPassword('$2y$13$placeholder_uniquement_pour_le_test_fonctionnel');
        // emailVerifie: true → la demande apparaît dans le tableau "À traiter" de l'admin
        $demande->setEmailVerifie(true);

        $em->persist($demande);
        $em->flush();

        return $demande;
    }
}
