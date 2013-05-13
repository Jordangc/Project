#ifndef _RANDOM_STREAM_

#define _RANDOM_STREAM_

#include <iostream>
#include <cmath>
#include <cassert>
#include <time.h>

using namespace std;

class randomStream
{
	public:

		// constructor -- set the value of the generator's seed > 0
		randomStream( long initSeed = 0 ) : modulus(0x7fffffff), multiplier(630360016), pi(3.14159)
		{
			if ( initSeed == 0 )
			{
				time_t ltime;
				time( &ltime );
				initSeed = static_cast<long>(ltime);
			}
			seed = initSeed;
		}

		// reset the value of the generator's seed > 0
		void setSeed( long initSeed = 0 )
		{
			if ( initSeed == 0 )
			{
				time_t ltime;
				time( &ltime );
				initSeed = static_cast<long>(ltime);
			}
			seed = initSeed;
		}

		// return a random integer 0...0x7fffffff
		long random()
		{
			seed = static_cast<long>((multiplier * seed) % modulus);
			return seed;
		}

		// return a random integer number in the interval {min, ... max}
		long intUniform( long min = 0, long max = 1 )
		{
			assert( min <= max );
			return min + (random() % (max - min + 1));
		}

		// return a random real number in the interval [min,max)
		double uniform( double min = 0.0, double max = 1.0 )
		{
			assert( min < max );
			return min + (max - min) * (static_cast<double>(random()) / static_cast<double>(modulus));
		}

		// return a Possion distributed random variable with the given mean
		long poisson( long mean )
		{
			double	a, b;
			int	i;

			a = exp( -static_cast<double>(mean) );
			b = 1.0;
			i = 0;

			do
			{
				b *= uniform();
				i++;
			} while ( a <= b );

			return (i-1);
		}

		// return an exponentially distributed random variable with the given mean
		double exponential( double mean )
		{
			double x;

			while ( (x = uniform()) == 0 );
			return -mean * log(x);
		}

		// return an normally distributed random variable mean + sigma * N(0,1)
		double normal( double mean = 0.0, double sigma = 1.0 )
		{
			static bool noGenerate;
			static double x2;

			double u1, u2, x1;

			if ( !noGenerate )
			{
				u1	= uniform();
				u2	= uniform();
				x1	= sqrt( -2.0 * log( u1 ) ) * cos( 2.0 * pi * u2 );
				x2	= sqrt( -2.0 * log( u1 ) ) * sin( 2.0 * pi * u2 );
			}
			else
			{
				x1	= x2;
			}
			noGenerate = !noGenerate;
			return mean + sigma * x1;
		}

		// return a Bernoulli distributed random variable
		int bernoulli( double probability = 0.5 )
		{
			return uniform() <= probability;
		}

		// return a binomial distributed random variable
		int binomial( int count = 1, double probability = 0.5 )
		{
			double x = 0.0;

			while ( count-- > 0 ) x += bernoulli( probability );
			return static_cast<int>(x);
		}

		// return a geometric distributed random variable
		int geometric( double probability = 0.5 )
		{
			double u;

			assert( probability > 0.0 && probability < 1.0 );

			while ( (u = uniform()) == 0 ) ;

			return static_cast<int>(floor( log(u) / log( 1.0 - probability )));
		}

	protected:
		
		const long int	modulus;
		const double	pi;
		const long int	multiplier;
		long			seed;
};


#endif