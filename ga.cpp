#include <iostream>
#include <fstream>
#include <cassert>
#include <iomanip>
#include <cmath>

#include "random.h"
#include "graph.h"

using namespace std;


/*
	The population manipulated by the GA consists of individuals. 				
*/

class individual
{
	public:
		// constructor
		individual( int sz = 1 );
		// destructor
		~individual();
		// copy constructor
		individual( const individual& );
		// overloaded assignment operator
		void operator=( const individual& );
		// print individual
		void print( ostream& = cout, bool = false );
		// compare fitness
		bool operator> ( const individual& );

		double	fitness;		// fitness
		int	currentSize;		// current size of set S
		int	maxSize;		// maximum size of set S
		bool*	set;			// set S
};

// initialize a member of the population

individual::individual( int sz )
{ 
	maxSize		= sz;
	fitness		= 0;
	currentSize	= 0;
	set		= new bool[maxSize];
}

// delete a member of the population

individual::~individual()
{
	delete [] set;
}

// copy one member of the population to another member

individual::individual( const individual& id )
{
	fitness		= id.fitness;
	currentSize	= id.currentSize;
	maxSize		= id.maxSize;
	set			= new bool[ maxSize ];
	for ( int i = 0; i < maxSize; i++ ) set[i] = id.set[i];
}

// assign one member of the population to another member

void individual::operator=( const individual& id )
{
	if ( this != &id )
	{
		delete [] set;

		fitness		= id.fitness;
		currentSize	= id.currentSize;
		maxSize		= id.maxSize;
		set		= new bool[ maxSize ];
		for ( int i = 0; i < maxSize; i++ ) set[i] = id.set[i];
	}
}

// compare fitness score (assuming we are maximizing fitness)

bool individual::operator> ( const individual& id )
{
	return fitness > id.fitness;
}

// print member, if prtAll flag is true, display the bit vector

void individual::print( ostream& os, bool prtAll )
{
   os << setw(8) << fitness << setw(8) << currentSize << endl;
   if ( prtAll )
   {
      int width		= 0;
      int	maxWidth	= 50;	
      os << "Set: ";
      for ( int k = 0; k < maxSize; k++ ) 
      {
         os << set[k];
         if ( ++width % maxWidth == 0 && k != maxSize - 1 ) os << endl << "     ";
      }
      os << endl;
   }	
}

// define a GA population composed of individuals

class population : virtual public randomStream
{
public:	
	population( int = 100, int = 100, long = 0, double = 0.1);
	~population();
	void		reproduce();	
	void		evaluate( individual& id );
	void		selectSurvivors();
	void		findAvgFitness();
	//void		findMinFitness();
	//void		findMaxFitness();
	void		printGreedy(ostream& = cout, bool = false);
	void		print( ostream& = cout, int = 0, int = 0, bool = false );

private:
	void		sort( int );

	// pointer to array of individuals
	individual*	pop;
	
	// Size of parent population
	int		basePopSize;
	// Population size of parents & offspring
	int		maxSize;
	
	// Size of individual chromosome
	int		chrSize;
	
	// current generation's average fitness
	double		avgFitness;
	
	// current generation's minimum fitness
	double		minFitness;
	
	// current generation's average fitness
	double		maxFitness;
	
	// Pointer to graph to be used 
	graph*		g;
};

/*
Initialize the population to a base size of pSz. Set the maximum 
size of the bit vector representing the chromosome to chrSz. 
Use the value pSd to set the seed for the random number
generator (pass 0 to use the system time to set the seed).
*/

population::population( int pSz, int chrSz, long pSd, double cn) : randomStream( 0 )
//population::population( int pSz, int chrSz, long pSd, double cn) : randomStream( pSd )
{

	int i, j;

	// initialize the graph
	g = new graph(chrSz,cn,0);
	//testing
	cout << "Graph g has been initialized" << endl;
	
	// set the population size
	basePopSize	= pSz;
	
	// set the chromosome size
	chrSize = chrSz;
	
	// set initial fitnesses
	minFitness = maxFitness = 0;
	avgFitness = 0.0;

	// assume the population doubles in size during reproduction
	maxSize		= 2 * basePopSize;

	// allocate space for base population + offspring
	pop = new individual[ maxSize ];

	// initialize the base population + offspring
	for ( i = 0; i < maxSize; i++ )
	{
		// allocate internal chromosome of each individual
		pop[i] = individual( chrSz );
	}

	for ( i = 0; i < basePopSize; i++ )			
	{
		// initialize the ith member's chromosome
		for ( j = 0; j < pop[i].maxSize; j++ )
		{
			pop[i].set[j] = (bernoulli() ? true : false);
		}
		// evalute the ith member
		evaluate( pop[i] );
	}
	// order the population based on the fitness (from high to low)
	sort( basePopSize );
}

// release the space allocated to the population

population::~population()
{
	delete [] pop;
	delete g;
}

// reproduce the population with variation (crossover + mutation)

void population::reproduce()
{
   int p1, p2; // indices of parents to breed
   int i, j;   // loop control
   int cp;     // cross over point
   int c1, c2; // indices of offspring
   int size1, size2;   // used to compute current size of new offspring
   int pos;    // position for mutation
   
   // create as many offspring as parents
   // each iteration creates two children
   for(i = 0; i < (basePopSize / 2); i++)
   {
      // randomly select two parents
      p1 = intUniform(0,basePopSize - 1);
      p2 = intUniform(0,basePopSize - 1);
      
      // select random cross-over point
      cp = intUniform(0,chrSize - 1); 
      // create children
      c1 = basePopSize + i*2;
      c2 = basePopSize + i*2 + 1;
      // initally, just copy child to match a parent
      pop[c1] = pop[p1];
      pop[c2] = pop[p2];
      // perform crossover for randomly selected portion of chromosome
      for(j = cp; j < chrSize; j++)
      {
         pop[c1].set[j] = pop[p2].set[j];
	 pop[c2].set[j] = pop[p1].set[j];
      }
      // mutation for child 1
      pos = 0;
      while(pos < chrSize)
      {
         // select position on chromosome for mutation
	 pos += geometric(1.0 / sqrt(chrSize)); 
	 if(pos < chrSize)
	 {
	    //flip bit at position chosen for mutation
	    if(pop[c1].set[pos] == 0)
	       pop[c1].set[pos] = 1;
	    else pop[c1].set[pos] = 0;
	 }
      }
      //repeat mutation for child 2
      pos = 0;
      while(pos < chrSize)
      {
         // select position on chromosome for mutation
	 pos += geometric(1.0 / sqrt(chrSize)); 
	 if(pos < chrSize)
	 {
	    //flip bit at position chosen for mutation
	    if(pop[c2].set[pos] == 0)
	       pop[c2].set[pos] = 1;
	    else pop[c2].set[pos] = 0;
	 }
      }
      // recalculate size
      size1 = size2 = 0;
      for(j = 0; j < chrSize; j++)
      {
         if(pop[c1].set[j] == 1)
	    size1++;
	 if(pop[c2].set[j] == 1)
	    size2++;
      }
      pop[c1].currentSize = size1;
      pop[c2].currentSize = size2;
   }
   // evaluate fitness of each offspring
   for(i = basePopSize; i < maxSize; i++)
   {
      evaluate(pop[i]);
   }
   return;
}

// evaluate the individual

void population::evaluate( individual& id )
{
   int fitness, i, j;
   bool flag = true;   // is this individual a valid solution
   bool present = false;  //is edge present
   fitness = 0;
   for(i = 0; i < chrSize; i++)
   {
       if(id.set[i] == 1) // this vertex is present in set
       {
          //make sure that it does not share an edge with an earlier vertex
	  for(j = 0; j < i; j++)
          {
             // if any edge is present, the solution is invalid
	     present = g->edgePresent(i, j);
	     if(present)
	     {
	        flag = false;
		break;
	     }
          }
	  //fitness increases if new vertex does not share edges with
	  //any present vertex
	  fitness++;
       }
       if(flag == false)  // not a valid solution
       {
          fitness = 0;
	  break;
       } 
   }
   id.fitness = fitness;
   return;
}

// elite selection for survival based on fitness
/*
This is a trivial function.  By the time that this function is called,
The population consists of both parents and offspring, and their
fitnesses are known.  All that must be done is to sort the population,
which currently is of max size.  After sorting, the most fit members will
be in the top half, so by default, the top half become survivors.
*/

void population::selectSurvivors()
{
   sort(maxSize);
   findAvgFitness();
   //individual at end of base population has worst fitness
   minFitness = pop[basePopSize-1].fitness;
   //individual at front of array has best fitness
   maxFitness = pop[0].fitness;
   return;
}

/* 
 * Function: findAvgFitness
 * Description: compute the average fitness for the current population
 * Precondition: Population has been sorted, so that most fit individuals
 *     are within the range [0..basePopSize-1] in population array
 */
void population::findAvgFitness()
{
   double avg = 0.0;
   int i;
   
   for(i = 0; i < basePopSize; i++)
   {
      avg += (double) pop[i].fitness;
   }
   avgFitness = avg / basePopSize;
}

// print solution of the standard greedy algorithm.

void population::printGreedy( ostream& os, bool prtAll )
{
   cout << "In print greedy, before allocating solSet\n";
   cout << "chrSize = " << chrSize << endl;
   bool* solSet; // solution set from Greedy algorithm.
   solSet = new bool[chrSize];
   cout << "In print greedy, after allocating solSet\n";
   int     solSize = 0;
   
   //testing
   cout << "printGreedy has been called." << endl;
   
   g->greedySolution( solSet, solSize);
   os << "Greedy solution size: " << solSize << endl;
   if(prtAll == true)
   {
      int width		= 0;
      int maxWidth	= 50;	
      os << "Set: ";
      for ( int k = 0; k < chrSize; k++ ) 
      {
         os << solSet[k];
         if ( ++width % maxWidth == 0 && k != maxSize - 1 ) os << endl << "     ";
      }
      os << endl;
   }
}

// print some subset of the population

void population::print( ostream& os, int cnt1, int cnt2, bool prtAll )
{
   int k;

   for ( k = cnt1; k <= cnt2; k++ )
   {
      pop[k].print( os, prtAll );
   }	
}

// sort population from largest to smallest for indices 0...n-1 based on fitness
// (simple selection sort -- not very efficient but acceptable for our purpose)

void population::sort( int n )
{
   for ( int i = 0; i < n - 1; i++ )
   {
      double maxFit = pop[i].fitness;
      int   maxIndex = i;

      for ( int j = i + 1; j < n; j++ )
      {
        if ( pop[j].fitness > maxFit )
        {
           maxFit = pop[j].fitness;
           maxIndex = j;
        }
      }

      individual   tmp;

      tmp = pop[i];
      pop[i] = pop[maxIndex];
      pop[maxIndex] = tmp;
   }
}



int main()
{	
	int graphSize;	 	// Number of vertices for graph
	double cn; 	 	// graph connectivity
	int basePopSize;  	// population size
	int maxGeneration;	// Number of generations to run GA
	int maxRep;		// Number of times to run GA
	char ch;
	bool sameNums;		// Use same paramaters each repetition?
	int generation; 	// current generation
	
	
	cout << "Enter number of repetitions to run GA: ";
	cin >> maxRep;
	if( maxRep <= 0)
	{
	   cerr << "Bad value for number of repetitions.";
	   exit(1);
	}
	
	cout << setprecision(3) << setiosflags(ios::fixed);
	
	if( maxRep > 1)
	{
	   cout << "Do you want to use the same parameters for each repitition (y/n)?: ";
	   cin >> ch;
	   if((ch == 'y') || (ch == 'Y'))
	   {
	      sameNums = true;
	   }
	   else 
	   {
	      sameNums = false;
	   }
	}
	
	for ( int r = 0; r < maxRep; r++ )
	{
#if 0
	   if((sameNums != true) || (r == 0))
	   {
              cout << "Enter parameters for repetition # " << r << endl;
	      
	      cout << "Enter number of graph vertices: ";
              cin >> graphSize;
              if( graphSize <= 0)
              {
	         cerr << "Bad value for graph size." << endl;
	         exit(1);
	      }
              cout << "Enter graph connectivity (0.0 < cn < 1.0): ";
              cin >> cn;
              if((cn < 0.0) || (cn > 1.0))
              {
	         cerr << "Bad value for cn." << endl;
	         exit(1);
              }
              cout << "Enter population size: ";
              cin >> basePopSize;
              if( basePopSize <= 0)
	      {
	         cerr << "Bad value for population size." << endl;
	         exit(1);
	      }
	      cout << "Enter number of generations: ";
	      cin >> maxGeneration;
	      if( maxGeneration <= 0)
	      {
	         cerr << "Bad value for number of generations." << endl;
	         exit(1);
	      }
	   }
	   
	   cout << endl << "Parameters for repetition # " << r << endl;
	   cout << endl << "Number of vertices: " << graphSize;
	   cout << endl << "Graph Connectivity: " << cn;
	   cout << endl << "Population size: " << basePopSize;
	   cout << endl << "Number of generations: " << maxGeneration;
	   cout << endl << endl;
#endif
           // testing values
	   r = 3;
	   graphSize = 25;
	   cn = 0.5;
	   basePopSize = 100;
	   maxGeneration = 100;

	   // Initial population of potential solutions
	   cout << "cn = " << cn;
	   bool check;
	   population	pop( basePopSize, graphSize, 0, cn );
	   for(int i = 0; i < 50; i++)
	   {
	      //check = (bernoulli() ? true : false);
	      check = (pop.bernoulli(cn) ? true : false);
	      cout <<(i+1)<<" bernoulli("<<cn<<") returned "<<check<<endl; 
	   }
	   
	   //population	pop( basePopSize, graphSize, 0, cn );
	   cout << "Pop constructor returned.\n";
	   // Evolutionary cycle
	   generation = 0;
	  // pop.printGreedy(cout, 1);
	   /*
	   while ( generation < maxGeneration )
	   {
	      // Reproduce (crossover + mutation)
	      pop.reproduce();
	      // Select survivors
	      pop.selectSurvivors();
	      generation++;
	   }
	   // Print the best in the population
	   pop.print();
	   */
	} //end repitition loop
	
	return 0;
}